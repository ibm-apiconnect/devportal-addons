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


libraries_load('underscore');
drupal_add_library('underscore', 'underscore');

$showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);
$showversions = variable_get('ibm_apim_show_versions', 1);
$codesnippets = variable_get('ibm_apim_codesnippets', array(
  'curl' => 1,
  'ruby' => 1,
  'python' => 1,
  'php' => 1,
  'java' => 1,
  'node' => 1,
  'go' => 1,
  'swift' => 1,
  'c' => 0,
  'csharp' => 0
));
$apim_session = &_ibm_apim_get_apim_session();
$productnid = $apim_session['productid'];
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'wsdl') {
  $protocol = 'wsdl';
}
else {
  $protocol = 'rest';
} ?>
<article id="node-<?php print $node->nid; ?>"
         class="mesh-portal-product singleapi <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> clearfix" <?php print $attributes; ?>>

  <header class="titleSection">
    <div class="titleInnerWrapper">
      <div class="leftTitle">

        <div class="productTitle apiTitle">
          <?php if (isset($content['api_image'])) : ?>
          <div class="apicApiIcon">
            <?php print render($content['api_image']); ?>
          </div>
          <?php elseif ($showplaceholders != 0) : ?>
          <div class="apicApiIcon">
            <img src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/api/' . api_random_image($node->title)); ?>" alt=""/>
          </div>
          <?php endif; ?>
          <h1 class="productName apiName"><?php print $node->title;?>
            <?php if ($showversions == 1): ?>
            <span class="productVersion apiVersion"><?php print check_plain($api['info']['version']); ?></span>
            <?php endif; ?> <?php if (isset($content['field_apirating'])) {
              $content['field_apirating']['#label_display'] = 'hidden';
              print render($content['field_apirating']);
            } ?></h1>

        </div>
      </div>
    </div>
  </header>
  <?php if (isset($node)) {
    $view = node_view($node, 'inner');
    print drupal_render($view);
  }
  else {
    print "<p>" . t('No API found.') . "</p>";
  } ?>

</article>
