// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {Input} from 'sulu-admin-bundle/containers/Form';

// "config_line" is a plain single-line text input used by the shared block
// fragments (attr_class, attr_id) for non-translatable configuration values.
// Registering it here makes the block library self-contained — it no longer
// depends on robole/sulu-ai-translator-bundle to provide this field type.
// If that bundle is also installed it registers the same key. fieldRegistry.add()
// throws on a duplicate key (crashing the whole admin JS at boot), so we guard
// the call and let whichever bundle registers first win.
if (!fieldRegistry.has('config_line')) {
    fieldRegistry.add('config_line', Input);
}
