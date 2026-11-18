// Approval workflow, end to end in the real admin: an Editor submits a change to a live page through
// PublishPress Revisions, the live page stays unchanged, an Editor cannot bypass review through the REST
// API, and a Reviewer approves with the keyboard, which publishes the change. Creates and deletes its own
// test page.
// Usage: node tests/approval-workflow.mjs [base-url]   (uses installed Google Chrome via channel "chrome")
import AxeBuilder from '@axe-core/playwright';
import { chromium } from 'playwright';

const base = process.argv[ 2 ] || 'https://tyler-cms.ddev.site';
const stamp = Date.now();
const original = `Original approval test text ${ stamp }`;
const revised = `Revised approval test text ${ stamp }`;
let failures = 0;
const check = ( ok, label ) => {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) {
		failures++;
	}
};

const browser = await chromium.launch( { channel: 'chrome' } );
const login = async ( user ) => {
	const ctx = await browser.newContext( { ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1000 } } );
	const page = await ctx.newPage();
	await page.goto( `${ base }/wp-login.php` );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', `${ user }-local-only` );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );
	return page;
};
/** Open a block editor screen and dismiss the welcome guide and pattern chooser. */
const openEditor = async ( page, path ) => {
	await page.goto( base + path, { waitUntil: 'domcontentloaded' } );
	await page.waitForFunction( () => window.wp?.data?.select( 'core/editor' )?.getCurrentPostId(), null, { timeout: 60000 } );
	await page.evaluate( () => {
		const { dispatch } = window.wp.data;
		dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
		dispatch( 'core/preferences' ).set( 'core', 'enableChoosePatternModal', false );
	} );
	await page.waitForTimeout( 1500 );
	for ( let i = 0; i < 5 && ( await page.locator( '.components-modal__screen-overlay' ).count() ); i++ ) {
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 300 );
	}
};
const api = ( page, options ) =>
	page.evaluate( ( o ) => window.wp.apiFetch( o ).then( ( body ) => ( { ok: true, body } ), ( e ) => ( { ok: false, code: e.code } ) ), options );
const liveText = async () => {
	const ctx = await browser.newContext( { ignoreHTTPSErrors: true } );
	const page = await ctx.newPage();
	await page.goto( `${ base }/?page_id=${ pageId }&nocache=${ Date.now() }` );
	const text = await page.locator( 'main' ).innerText();
	await ctx.close();
	return text;
};

const reviewer = await login( 'reviewer1' );
const editor = await login( 'editor1' );
await openEditor( reviewer, '/wp-admin/post-new.php?post_type=page' );
const created = await api( reviewer, { path: '/wp/v2/pages', method: 'POST', data: { title: `Approval test ${ stamp }`, status: 'publish', content: `<!-- wp:paragraph --><p>${ original }</p><!-- /wp:paragraph -->` } } );
check( created.ok, 'reviewer1 publishes a test page' );
const pageId = created.body?.id;
let revisionId = 0;

try {
	// Editor: new content goes to review, live content can't be changed directly.
	await openEditor( editor, '/wp-admin/post-new.php?post_type=page' );
	check( ( await editor.getByRole( 'button', { name: 'Submit for Review' } ).count() ) > 0, 'editor1 sees "Submit for Review" on a new page' );
	check( 0 === ( await editor.getByRole( 'button', { name: 'Publish', exact: true } ).count() ), 'editor1 has no Publish button' );
	const bypass = await api( editor, { path: `/wp/v2/pages/${ pageId }`, method: 'POST', data: { content: 'Bypass attempt' } } );
	check( ! bypass.ok && 'rest_cannot_edit' === bypass.code, `REST: editor1 cannot edit the live page directly (${ bypass.code || 'allowed' })` );
	const draft = await api( editor, { path: '/wp/v2/pages', method: 'POST', data: { title: `Approval draft ${ stamp }`, status: 'publish' } } );
	check( ! draft.ok && 'rest_cannot_publish' === draft.code, `REST: editor1 cannot publish (${ draft.code || 'allowed' })` );
	const trash = await api( editor, { path: `/wp/v2/pages/${ pageId }`, method: 'DELETE' } );
	check( ! trash.ok && 'rest_cannot_delete' === trash.code, `REST: editor1 cannot delete the live page (${ trash.code || 'allowed' })` );

	// Editor: create a revision of the live page and submit it.
	await editor.goto( `${ base }/wp-admin/edit.php?post_type=page` );
	const createLink = editor.locator( `#post-${ pageId } .row-actions a` ).filter( { hasText: 'Create Revision' } );
	check( 1 === ( await createLink.count() ), 'editor1 has "Create Revision" on the live page' );
	await openEditor( editor, new URL( await createLink.getAttribute( 'href' ) ).pathname + new URL( await createLink.getAttribute( 'href' ) ).search );
	revisionId = await editor.evaluate( () => window.wp.data.select( 'core/editor' ).getCurrentPostId() );
	check( revisionId && revisionId !== pageId, `editor1 is editing a separate revision (${ revisionId })` );
	await editor.evaluate( ( text ) => {
		const block = window.wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => 'core/paragraph' === b.name );
		window.wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( block.clientId, { content: text } );
	}, revised );
	await editor.getByRole( 'button', { name: 'Submit Revision' } ).click();
	await editor.getByText( 'Submitted Revision' ).first().waitFor( { timeout: 30000 } );
	check( true, 'editor1 submits the revision (status "Submitted Revision")' );
	const pending = await liveText();
	check( pending.includes( original ) && ! pending.includes( revised ), 'live page is unchanged while the revision waits for approval' );

	// Reviewer: find it in the queue and approve with the keyboard.
	await reviewer.goto( `${ base }/wp-admin/admin.php?page=revisionary-q` );
	check( ( await reviewer.locator( '#the-list' ).innerText() ).includes( `Approval test ${ stamp }` ), 'reviewer1 sees the submission in Revisions > New Revisions' );
	const queueAxe = await new AxeBuilder( { page: reviewer } ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] ).include( '#wpbody-content' ).analyze();
	console.log( `INFO  revision queue axe WCAG 2.2 AA violations: ${ queueAxe.violations.map( ( v ) => `${ v.id } (${ v.nodes.length })` ).join( ', ' ) || 'none' }` );
	await openEditor( reviewer, `/wp-admin/post.php?post=${ revisionId }&action=edit` );
	const approve = reviewer.locator( 'a.revision-approve button' ).first();
	check( ( await approve.innerText() ).includes( 'Publish Revision' ), 'reviewer1 sees "Publish Revision"' );
	await approve.focus();
	await Promise.all( [ reviewer.waitForNavigation( { timeout: 30000 } ), reviewer.keyboard.press( 'Enter' ) ] );
	const live = await liveText();
	check( live.includes( revised ) && ! live.includes( original ), 'approved revision is live (approved with the keyboard)' );
	revisionId = 0;
} finally {
	if ( pageId ) {
		await openEditor( reviewer, `/wp-admin/post.php?post=${ pageId }&action=edit` ); // Loads wp.apiFetch again.
	}
	if ( revisionId ) {
		await api( reviewer, { path: `/wp/v2/pages/${ revisionId }?force=true`, method: 'DELETE' } );
	}
	if ( pageId ) {
		const removed = await api( reviewer, { path: `/wp/v2/pages/${ pageId }?force=true`, method: 'DELETE' } );
		check( removed.ok, 'reviewer1 deletes the test page (Reviewers can delete live content)' );
	}
	await browser.close();
}

console.log( failures ? `\n${ failures } failure(s)` : '\nAll passed (0 failures)' );
process.exit( failures ? 1 : 0 );
