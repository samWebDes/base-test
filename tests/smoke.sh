#!/usr/bin/env bash
# HTTP smoke + authorization tests against the DDEV site. Usage: tests/smoke.sh [base-url]
set -u
BASE="${1:-https://tyler-cms.ddev.site}"
P=cms
PASS=0; FAIL=0
ok()   { PASS=$((PASS+1)); echo "PASS  $1"; }
bad()  { FAIL=$((FAIL+1)); echo "FAIL  $1"; }
code() { curl -sk -o /dev/null -w '%{http_code}' "$@"; }
expect_code() { local want="$1" label="$2"; shift 2; local got; got=$(code "$@"); [ "$got" = "$want" ] && ok "$label ($got)" || bad "$label (want $want got $got)"; }
expect_body() { local pattern="$1" label="$2"; shift 2; curl -sk "$@" | grep -qE "$pattern" && ok "$label" || bad "$label (pattern '$pattern' not found)"; }
expect_no_body() { local pattern="$1" label="$2"; shift 2; curl -sk "$@" | grep -qE "$pattern" && bad "$label (pattern '$pattern' found)" || ok "$label"; }

echo "== Public pages"
for p in / /services/ /locations/ /events/ /alerts/ /documents/ /faqs/ /people/ /news/ /about/ /contact/ /wp-sitemap.xml; do expect_code 200 "GET $p" "$BASE$p"; done
expect_code 404 "GET unknown path is 404" "$BASE/no-such-page-xyz/"
expect_body 'cmscoe-entry-fields' "service detail server-renders its fields (Entry fields)" "$BASE/services/apply-for-a-business-license/"
expect_body 'Processing time' "service detail shows field labels" "$BASE/services/apply-for-a-business-license/"
expect_body '"@type":"GovernmentService"' "service detail has GovernmentService JSON-LD" "$BASE/services/apply-for-a-business-license/"
expect_body '"@type":"GovernmentOffice"' "location detail has GovernmentOffice JSON-LD" "$BASE/locations/central-office/"
expect_no_body 'Latitude' "coordinates hidden from visible field list" "$BASE/locations/central-office/"
expect_body '"@type":"Event"' "event detail has Event JSON-LD" "$BASE/events/public-hearing-on-proposed-rules/"
expect_body '<time datetime=' "event dates are machine-readable <time>" "$BASE/events/public-hearing-on-proposed-rules/"
expect_body '"@type":"FAQPage"' "FAQ archive has FAQPage JSON-LD" "$BASE/faqs/"
expect_body '<forge-expansion-panel' "FAQ archive renders Forge expansion panels" "$BASE/faqs/"
expect_body 'Renew online up to 60 days' "FAQ answers are in the server HTML" "$BASE/faqs/"
expect_body '"@type":"GovernmentOrganization"' "home has GovernmentOrganization JSON-LD" "$BASE/"
expect_body '<forge-button' "home renders Forge button" "$BASE/"
expect_body '<forge-card' "home renders Forge cards" "$BASE/"
expect_body '<forge-inline-message theme="warning"' "active alert shown site-wide" "$BASE/about/"
expect_body 'Warning: North Service Center' "alert severity spelled out in text" "$BASE/about/"
expect_body 'forge-expansion-panel' "About page renders the FAQ accordion pattern" "$BASE/about/"
expect_body 'cmscoe-directory__filters' "services directory renders filters" "$BASE/services/"
expect_body '2 results in Services' "directory filter by audience (businesses)" "$BASE/services/?f_${P}_audience=businesses"
expect_body '1 result in Services' "directory keyword search" "$BASE/services/?q=record"
expect_body 'forge-runtime.js' "Forge runtime enqueued on pages using Forge blocks" "$BASE/"
n=$(curl -sk "$BASE/" | grep -oF 'forge-runtime.js?' | wc -l | tr -d ' '); [ "$n" = "1" ] && ok "Forge runtime loaded exactly once ($n)" || bad "Forge runtime loaded $n times"
expect_body 'body-normal.woff2' "self-hosted brand font emitted" "$BASE/"
expect_body "wp-sitemap-posts-${P}_service" "sitemap index lists services" "$BASE/wp-sitemap.xml"
expect_no_body 'acf-' "sitemap excludes builder internals" "$BASE/wp-sitemap.xml"

echo "== Environment isolation (non-production)"
expect_body 'noindex' "robots meta noindex on DDEV" "$BASE/"
expect_body 'Disallow: /' "robots.txt disallows all on DDEV" "$BASE/robots.txt"
curl -skI "$BASE/" | grep -qi 'x-robots-tag: noindex' && ok "X-Robots-Tag header" || bad "X-Robots-Tag header missing"

echo "== Anonymous REST / enumeration"
expect_code 404 "anon /wp/v2/users hidden" "$BASE/wp-json/wp/v2/users"
expect_code 200 "anon REST list of services is public" "$BASE/wp-json/wp/v2/${P}_service"
got=$(code "$BASE/wp-json/wp/v2/${P}_service?status=draft"); { [ "$got" = 400 ] || [ "$got" = 401 ]; } && ok "anon cannot list draft services ($got)" || bad "anon draft services ($got)"
expect_code 301 "?author=1 enumeration redirected" "$BASE/?author=1"
expect_no_body 'wp.getUsersBlogs|demo.sayHello' "xmlrpc exposes no WordPress methods" -X POST -d '<methodCall><methodName>system.listMethods</methodName></methodCall>' "$BASE/xmlrpc.php"

echo "== Authenticated role checks"
login() { local jar="$1" user="$2" pass="$3"
  curl -sk -c "$jar" -b "wordpress_test_cookie=WP%20Cookie%20check" -o /dev/null \
    --data-urlencode "log=$user" --data-urlencode "pwd=$pass" -d "wp-submit=Log+In&testcookie=1&redirect_to=$BASE/wp-admin/" "$BASE/wp-login.php"; }
TMP=$(mktemp -d)
login "$TMP/admin" admin admin-local-only
login "$TMP/editor" editor1 editor1-local-only
login "$TMP/reviewer" reviewer1 reviewer1-local-only
for role in admin editor reviewer; do expect_code 200 "$role reaches dashboard" -b "$TMP/$role" "$BASE/wp-admin/"; done
for screen in "edit.php?post_type=acf-field-group" "edit.php?post_type=acf-post-type" "edit.php?post_type=acf-taxonomy" "plugins.php" "plugin-install.php" "users.php" "options-general.php?page=cmscoe-admin-skin" "edit.php?post_type=acf-field-group&page=acf-tools" "admin.php?page=roles" "options-general.php?page=menu_editor" "site-editor.php" "site-editor.php?p=%2Fstyles" "site-editor.php?p=%2Ftemplate"; do
  expect_code 200 "admin can open $screen" -b "$TMP/admin" "$BASE/wp-admin/$screen"
  for role in editor reviewer; do
    got=$(code -b "$TMP/$role" "$BASE/wp-admin/$screen")
    [ "$got" = "403" ] || [ "$got" = "302" ] && ok "$role denied $screen ($got)" || bad "$role reached $screen ($got)"
  done
done
for role in editor reviewer; do
  got=$(code -b "$TMP/$role" "$BASE/wp-json/acf/v1/" ); ok "$role acf REST root returns $got (informational)"
done
expect_code 200 "login page renders" "$BASE/wp-login.php"
expect_body 'cmscoe-login' "custom login body class present" "$BASE/wp-login.php"
expect_body 'cmscoe-login__aside' "login decorative column present" "$BASE/wp-login.php"
rm -rf "$TMP"
echo; echo "$PASS passed, $FAIL failed"; [ "$FAIL" = 0 ]
