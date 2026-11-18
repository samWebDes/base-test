/*
 * Copy self-hosted variable fonts from npm (@fontsource-variable/*) into the theme and point the
 * theme.json fontFace entries at the files that exist (not every family ships an italic).
 * bin/fonts.json (written by scripts/new-site.mjs) maps the theme.json font slugs "body" and
 * "heading" to packages. Run from the repo root: npm run build.
 */
import { readFileSync, writeFileSync, copyFileSync, mkdirSync, existsSync, readdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const fonts = JSON.parse( readFileSync( join( root, 'bin/fonts.json' ), 'utf8' ) );
const out = join( root, 'wp-content/themes', fonts.theme, 'assets/fonts' );
mkdirSync( out, { recursive: true } );

const themeJsonPath = join( root, 'wp-content/themes', fonts.theme, 'theme.json' );
const themeJson = JSON.parse( readFileSync( themeJsonPath, 'utf8' ) );
const faces = {};
const copied = new Map();
for ( const [ slug, font ] of Object.entries( fonts.families ) ) {
	if ( copied.has( font.package ) ) {
		continue; // Heading uses the same files (and @font-face rules) as body.
	}
	faces[ slug ] = [];
	const pkg = join( root, 'node_modules', font.package );
	const id = font.package.split( '/' ).pop();
	for ( const style of [ 'normal', 'italic' ] ) {
		const src = join( pkg, 'files', `${ id }-latin-wght-${ style }.woff2` );
		if ( existsSync( src ) ) {
			copyFileSync( src, join( out, `${ slug }-${ style }.woff2` ) );
			faces[ slug ].push( { fontFamily: font.family, fontStyle: style, fontWeight: '100 900', fontDisplay: 'swap', src: [ `file:./assets/fonts/${ slug }-${ style }.woff2` ] } );
		} else if ( 'normal' === style ) {
			throw new Error( `${ src } not found. Is ${ font.package } a variable font package?` );
		}
	}
	const license = readdirSync( pkg ).find( ( f ) => /^licen[cs]e/i.test( f ) );
	if ( license ) {
		copyFileSync( join( pkg, license ), join( out, `LICENSE-${ id }.txt` ) );
	}
	copied.set( font.package, slug );
	console.log( `Font ${ font.family } (${ slug }) copied from ${ font.package }` );
}

for ( const family of themeJson.settings.typography.fontFamilies ) {
	if ( faces[ family.slug ] ) {
		family.fontFace = faces[ family.slug ];
	} else {
		delete family.fontFace;
	}
}
writeFileSync( themeJsonPath, JSON.stringify( themeJson, null, '\t' ) + '\n' );
