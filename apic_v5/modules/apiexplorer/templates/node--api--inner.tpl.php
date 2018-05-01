<?php

/**
 * @file
 * Default theme implementation for apis.
 *
 * @see template_preprocess()
 * @see template_preprocess_api()
 * @see template_process()
 * @see theme_api()
 *
 * @ingroup themeable
 */

drupal_add_css(drupal_get_path('module', 'apiexplorer') . '/css/apiexplorer.css');

if (file_exists(drupal_get_path('module', 'apiexplorer') . '/explorer/app/asset-manifest.json')) {
  $string = file_get_contents(drupal_get_path('module', 'apiexplorer') . '/explorer/app/asset-manifest.json');
  $json = json_decode($string, TRUE);
  if (isset($json['main.js']) && file_get_contents(drupal_get_path('module', 'apiexplorer') . '/explorer/app/' . $json['main.js'])) {
    drupal_add_js(drupal_get_path('module', 'apiexplorer') . '/explorer/app/' . $json['main.js'], array(
      'weight' => 30,
      'type' => 'file',
      'minified' => TRUE,
      'preprocess' => FALSE,
      'defer' => TRUE
    ));
    drupal_add_css(drupal_get_path('module', 'apiexplorer') . '/explorer/app/css/explorer.css');
  }
}
drupal_add_js(drupal_get_path('module', 'apiexplorer') . '/js/explorer-callback.js', array(
  'type' => 'file'
));
$showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);

$apim_session = &_ibm_apim_get_apim_session();
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'wsdl') {
  $protocol = 'wsdl';
}
else {
  $protocol = 'rest';
}

?>
<article id="node-inner-<?php print $node->nid; ?>"
         class="mesh-portal-api <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?> inner clearfix" <?php print $attributes; ?>>
  <div class="bx--global-light-ui explorerWrapper">
    <apiconnect-explorer>
      <div class="spinner"></div>
    </apiconnect-explorer>
  </div>
</article>
