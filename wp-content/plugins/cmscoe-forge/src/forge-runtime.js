/**
 * CMS COE shared Forge runtime.
 *
 * Only the Forge components used by registered CMS COE blocks are imported, so the
 * bundle stays small. Add a component here only when a block or pattern needs it.
 */
import '@tylertech/forge/button';
import '@tylertech/forge/card';
import '@tylertech/forge/expansion-panel';
import '@tylertech/forge/open-icon';
import '@tylertech/forge/inline-message';

window.cmscoeForge = window.cmscoeForge || { version: __FORGE_VERSION__ };
