// Automated accessibility scan (axe-core, WCAG 2.0/2.1/2.2 A+AA tags) of key templates at
// mobile and desktop widths, plus a keyboard check that the skip link and Forge buttons are focusable.
// Usage: node tests/a11y.mjs [base-url]   (uses installed Google Chrome via channel "chrome")
// Automated results alone are NOT acceptance (accessibility.md) — manual SR/zoom checks still required.
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const base = process.argv[2] || 'https://tyler-cms.ddev.site';
const paths = [ '/', '/services/', '/services/?f_cms_audience=businesses', '/services/apply-for-a-business-license/', '/locations/central-office/', '/events/', '/events/public-hearing-on-proposed-rules/', '/faqs/', '/news/', '/about/', '/contact/', '/search/?s=license', '/wp-login.php', '/no-such-page/' ];
const viewports = [ { name: 'mobile', width: 375, height: 800 }, { name: 'desktop', width: 1280, height: 900 } ];

const browser = await chromium.launch( { channel: 'chrome' } );
let violations = 0;
for ( const vp of viewports ) {
	const ctx = await browser.newContext( { ignoreHTTPSErrors: true, viewport: { width: vp.width, height: vp.height } } );
	const page = await ctx.newPage();
	for ( const p of paths ) {
		await page.goto( base + p, { waitUntil: 'networkidle' } );
		await page.waitForTimeout( 300 ); // allow Forge custom elements to upgrade
		const res = await new AxeBuilder( { page } ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] ).analyze();
		violations += res.violations.length;
		const summary = res.violations.map( ( v ) => `${ v.id }(${ v.impact }) x${ v.nodes.length }: ${ v.nodes.slice( 0, 2 ).map( ( n ) => n.target.join( ' ' ) ).join( ' | ' ) }` );
		console.log( `${ res.violations.length ? 'FAIL' : 'PASS' }  [${ vp.name }] ${ p }  violations=${ res.violations.length } incomplete=${ res.incomplete.length }` );
		summary.forEach( ( s ) => console.log( '      - ' + s ) );
		// Horizontal overflow check (reflow at 320 CSS px equivalent at mobile width).
		const overflow = await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth + 1 );
		if ( overflow ) {
			violations++;
			console.log( `FAIL  [${ vp.name }] ${ p } horizontal overflow (reflow)` );
		}
	}
	await ctx.close();
}

// Keyboard: first Tab lands on skip link; Forge button link reachable and has visible focus outline.
const ctx = await browser.newContext( { ignoreHTTPSErrors: true } );
const page = await ctx.newPage();
await page.goto( base + '/', { waitUntil: 'networkidle' } );
await page.keyboard.press( 'Tab' );
const first = await page.evaluate( () => document.activeElement?.textContent?.trim() );
console.log( `${ first === 'Skip to content' ? 'PASS' : 'FAIL' }  first Tab focuses skip link (got "${ first }")` );
if ( first !== 'Skip to content' ) violations++;
let reached = false;
for ( let i = 0; i < 40 && ! reached; i++ ) {
	await page.keyboard.press( 'Tab' );
	reached = await page.evaluate( () => !! document.activeElement?.closest( 'forge-button' ) );
}
const outline = reached ? await page.evaluate( () => getComputedStyle( document.activeElement ).outlineStyle + ' ' + getComputedStyle( document.activeElement ).outlineWidth ) : 'n/a';
console.log( `${ reached ? 'PASS' : 'FAIL' }  Forge button link reachable by keyboard (focus outline: ${ outline })` );
if ( ! reached ) violations++;
// Accordion: the trigger is a button, Enter toggles it, and aria-expanded follows.
await page.goto( base + '/faqs/', { waitUntil: 'networkidle' } );
await page.waitForTimeout( 300 );
const trigger = page.locator( '.cmscoe-accordion__trigger' ).first();
await trigger.focus();
const before = await trigger.getAttribute( 'aria-expanded' );
await page.keyboard.press( 'Enter' );
await page.waitForTimeout( 400 );
const after = await trigger.getAttribute( 'aria-expanded' );
const toggled = 'false' === before && 'true' === after;
console.log( `${ toggled ? 'PASS' : 'FAIL' }  accordion toggles from the keyboard (aria-expanded ${ before } -> ${ after })` );
if ( ! toggled ) violations++;
// Mobile menu overlay: links must be readable on the overlay background.
await page.setViewportSize( { width: 375, height: 800 } );
await page.goto( base + '/', { waitUntil: 'networkidle' } );
const openMenu = page.locator( '.wp-block-navigation__responsive-container-open' );
if ( await openMenu.count() ) {
	await openMenu.click();
	await page.waitForTimeout( 300 );
	const overlay = await new AxeBuilder( { page } ).include( '.wp-block-navigation__responsive-container' ).withTags( [ 'wcag2aa' ] ).analyze();
	console.log( `${ overlay.violations.length ? 'FAIL' : 'PASS' }  mobile menu overlay has no AA violations (${ overlay.violations.map( ( v ) => v.id ).join( ',' ) || 'none' })` );
	violations += overlay.violations.length;
}
// Zoom/reflow: 320px viewport on a content page.
await page.setViewportSize( { width: 320, height: 700 } );
await page.goto( base + '/services/apply-for-a-business-license/', { waitUntil: 'networkidle' } );
const ovf = await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth + 1 );
console.log( `${ ovf ? 'FAIL' : 'PASS' }  service detail reflows at 320px without horizontal scroll` );
if ( ovf ) violations++;
await browser.close();
console.log( `\nTotal issues: ${ violations }` );
process.exit( violations ? 1 : 0 );
