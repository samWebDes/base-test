// Design lock, checked in the real block editor: Editors and Reviewers get content controls only;
// Administrators keep design controls. Logs in as each local user, opens a new page, inserts blocks,
// and inspects the block settings sidebar.
// Usage: node tests/editor-lock.mjs [base-url]   (uses installed Google Chrome via channel "chrome")
import { chromium } from 'playwright';

const base = process.argv[ 2 ] || 'https://tyler-cms.ddev.site';
const users = [
	{ login: 'admin', pass: 'admin-local-only', admin: true },
	{ login: 'editor1', pass: 'editor1-local-only', admin: false },
	{ login: 'reviewer1', pass: 'reviewer1-local-only', admin: false },
];
let failures = 0;
const check = ( ok, label ) => {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label }` );
	if ( ! ok ) {
		failures++;
	}
};

const browser = await chromium.launch( { channel: 'chrome' } );
for ( const user of users ) {
	const ctx = await browser.newContext( { ignoreHTTPSErrors: true, viewport: { width: 1440, height: 1000 } } );
	const page = await ctx.newPage();
	await page.goto( `${ base }/wp-login.php` );
	await page.fill( '#user_login', user.login );
	await page.fill( '#user_pass', user.pass );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );

	await page.goto( `${ base }/wp-admin/post-new.php?post_type=page`, { waitUntil: 'domcontentloaded' } );
	await page.waitForFunction( () => window.wp?.data?.select( 'core/block-editor' )?.getSettings && window.wp.blocks.getBlockType( 'core/paragraph' ), null, { timeout: 60000 } );
	await page.evaluate( () => {
		const { dispatch } = window.wp.data;
		dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
		dispatch( 'core/preferences' ).set( 'core', 'distractionFree', false );
		dispatch( 'core/preferences' ).set( 'core', 'enableChoosePatternModal', false );
	} );
	// The welcome guide or "Choose a pattern" modal can open after load, until the preferences save.
	const closeModals = async () => {
		for ( let i = 0; i < 5 && ( await page.locator( '.components-modal__screen-overlay' ).count() ); i++ ) {
			await page.keyboard.press( 'Escape' );
			await page.waitForTimeout( 300 );
		}
	};
	await page.waitForTimeout( 1500 );
	await closeModals();
	const who = user.login;

	// Settings the editor actually received.
	const s = await page.evaluate( () => {
		const st = window.wp.data.select( 'core/block-editor' ).getSettings();
		const f = st.__experimentalFeatures || {};
		return {
			palette: ( f.color?.palette?.theme || [] ).length,
			fontSizes: ( f.typography?.fontSizes?.theme || [] ).length,
			layout: f.layout?.allowEditing,
			code: st.codeEditingEnabled,
			imageStyles: ( window.wp.data.select( 'core/blocks' ).getBlockStyles( 'core/image' ) || [] ).length,
			highlight: !! window.wp.data.select( 'core/rich-text' ).getFormatType( 'core/text-color' ),
		};
	} );
	if ( user.admin ) {
		check( false !== s.code, `${ who }: code editor available` );
		check( s.imageStyles > 0 && s.highlight, `${ who }: block style variations and Highlight format available` );
	} else {
		check( false === s.code, `${ who }: code editor disabled` );
		check( 0 === s.imageStyles && ! s.highlight, `${ who }: no block style variations or Highlight (text color) format` );
	}

	// Select a block and read the sidebar, including the Styles tab when there is one.
	const inspect = async ( name, attrs = {}, inner = [] ) => {
		await closeModals();
		await page.evaluate( ( [ n, a, i ] ) => {
			const { createBlock } = window.wp.blocks;
			const block = createBlock( n, a, i.map( ( [ cn, ca ] ) => createBlock( cn, ca ) ) );
			window.wp.data.dispatch( 'core/block-editor' ).resetBlocks( [ block ] );
			window.wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
			window.wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/block' );
		}, [ name, attrs, inner ] );
		await page.waitForSelector( '.block-editor-block-inspector', { timeout: 15000 } );
		await page.waitForTimeout( 400 );
		// Sidebar text without the block card (title and description), so descriptions that mention
		// "layout" or "background" are not mistaken for controls.
		const controls = () => page.evaluate( () => {
			const inspector = document.querySelector( '.block-editor-block-inspector' );
			let text = inspector.innerText;
			inspector.querySelectorAll( '.block-editor-block-card' ).forEach( ( card ) => {
				text = text.replace( card.innerText, '' );
			} );
			return text;
		} );
		let text = await controls();
		// The Styles tab is icon-only; find it by its accessible name.
		const stylesTab = page.locator( '.block-editor-block-inspector [role="tab"][aria-label="Styles"], .block-editor-block-inspector [role="tab"]:has-text("Styles")' );
		const hasStylesTab = ( await stylesTab.count() ) > 0;
		if ( hasStylesTab ) {
			await stylesTab.first().click();
			await page.waitForTimeout( 300 );
			text += '\n' + ( await controls() );
		}
		return { text, hasStylesTab };
	};
	// Labels of design controls. An empty Typography header with no options may remain.
	const designControls = /Font size|Color|Background|Line height|Letter spacing|Appearance|Decoration|Letter case|Padding|Margin|Block spacing|Border|Radius|Dimensions|Minimum height|Justification|Orientation|Content width|Wide width|Minimum column width|Grid|Shadow/i;

	const para = await inspect( 'core/paragraph', { content: 'Test' } );
	if ( user.admin ) {
		check( /Font size/i.test( para.text ) && /Color/i.test( para.text ), `${ who }: paragraph shows Color and Font size controls` );
	} else {
		check( ! para.hasStylesTab && ! designControls.test( para.text ), `${ who }: paragraph has no color, typography, spacing, border, or dimension controls` );
	}

	const section = await inspect( 'cmscoe/section', {}, [ [ 'core/paragraph', { content: 'Inside' } ] ] );
	if ( user.admin ) {
		check( section.hasStylesTab && /Color|Background/i.test( section.text ), `${ who }: Section shows color and background controls` );
	} else {
		check( ! section.hasStylesTab && ! designControls.test( section.text ), `${ who }: Section has no layout, color, background, or spacing controls` );
	}

	const accordion = await inspect( 'cmscoe/forge-accordion', {}, [ [ 'cmscoe/forge-accordion-item', { heading: 'Question?' } ] ] );
	const accordionOptions = /Only one section open|Section heading level/i.test( accordion.text );
	if ( user.admin ) {
		check( accordionOptions, `${ who }: Forge Accordion shows its options` );
	} else {
		check( ! accordionOptions && ! accordion.hasStylesTab && ! designControls.test( accordion.text ), `${ who }: Forge Accordion shows no options or styling (text only)` );
	}

	// Select the accordion section (child block): only its text should be editable.
	await page.evaluate( () => {
		const be = window.wp.data.select( 'core/block-editor' );
		const item = be.getBlocks()[ 0 ].innerBlocks[ 0 ];
		window.wp.data.dispatch( 'core/block-editor' ).selectBlock( item.clientId );
	} );
	await page.waitForTimeout( 400 );
	const itemText = await page.locator( '.block-editor-block-inspector' ).innerText();
	check( user.admin === /Open when the page loads/i.test( itemText ), `${ who }: accordion section "open on load" option ${ user.admin ? 'shown' : 'hidden' }` );

	const button = await inspect( 'cmscoe/forge-button', { text: 'Go', url: '/services/' } );
	check( user.admin === /\bStyle\b/i.test( button.text ), `${ who }: Forge Button style choice ${ user.admin ? 'shown' : 'hidden' }` );
	check( /Destination URL/i.test( button.text ), `${ who }: Forge Button link is still editable` );

	// Leave without saving.
	await page.evaluate( () => window.wp.data.dispatch( 'core/block-editor' ).resetBlocks( [] ) );
	await ctx.close();
}
await browser.close();
console.log( `\n${ failures ? 'FAILED' : 'All passed' } (${ failures } failures)` );
process.exit( failures ? 1 : 0 );
