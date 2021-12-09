<?php

/**
 * @file
 * Contains \Drupal\views_nested_details\Plugin\views\style\NestedDetailsStyle.
 */

namespace Drupal\views_nested_details\Plugin\views\style;

use Drupal\views\Annotation\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Details style plugin to render rows as details.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "nested_details",
 *   title = @Translation("Nested Details"),
 *   help = @Translation("Displays rows as details, supports nested groups."),
 *   theme = "views_view_nested_details",
 *   display_types = {"normal"}
 * )
 */
class NestedDetailsStyle extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Should field labels be enabled by default.
   *
   * @var bool
   */
  protected $defaultFieldLabels = TRUE;

  /**
   * Contains the current active sort column.
   * @var string
   */
  public $active;

  /**
   * Contains the current active sort order, either desc or asc.
   * @var string
   */
  public $order;

  protected $groupingTheme = 'views_view_nested_details_section_grouping';

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {

    $options = parent::defineOptions();
    $options['collapsed'] = array('default' => FALSE);
    $options['open_first'] = array('default' => TRUE);
    $options['title'] = array('default' => '');
    $options['description'] = array('default' => '');
    $options['override'] = ['default' => TRUE];

    return $options;
  }

  /**
   * Render the display in this style.
   */
  public function render() {
    if ($this->usesRowPlugin() && empty($this->view->rowPlugin)) {
      trigger_error('Drupal\views\Plugin\views\style\StylePluginBase: Missing row plugin', E_WARNING);
      return [];
    }

    // Group the rows according to the grouping instructions, if specified.
    $sets = $this->renderGrouping(
      $this->view->result,
      $this->options['grouping'],
      TRUE
    );

    return $this->renderGroupingSets($sets);
  }

  /**
   * Render the grouping sets.
   *
   * Plugins may override this method if they wish some other way of handling
   * grouping.
   *
   * @param $sets
   *   An array keyed by group content containing the grouping sets to render.
   *   Each set contains the following associative array:
   *   - group: The group content.
   *   - level: The hierarchical level of the grouping.
   *   - rows: The result rows to be rendered in this group..
   *
   * @return array
   *   Render array of grouping sets.
   */
  public function renderGroupingSets($sets) {
    $output = [];
    $theme_functions = $this->view->buildThemeFunctions($this->groupingTheme);
    foreach ($sets as $set) {
      $level = $set['level'] ?? 0;

      $row = reset($set['rows']);
      // Render as a grouping set.
      if (is_array($row) && isset($row['group'])) {
        $single_output = [
          '#theme' => $theme_functions,
          '#view' => $this->view,
          '#grouping' => $this->options['grouping'][$level],
          '#rows' => $set['rows'],
        ];
      }
      // Render as a record set.
      else {
        if ($this->usesRowPlugin()) {
          foreach ($set['rows'] as $index => $row) {
            $this->view->row_index = $index;
            $set['rows'][$index] = $this->view->rowPlugin->render($row);
          }
        }

        $single_output = $this->renderRowGroup($set['rows']);
      }

      $single_output['#grouping_level'] = $level;
      $single_output['#title'] = $set['group'];
      $output[] = $single_output;
    }
    unset($this->view->row_index);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $form['collapsed'] = array(
      '#title' => $this->t('Collapsed by default'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check to show details collapsed.'),
      '#default_value' => $this->options['collapsed'],
      '#weight' => -49,
    );

    $form['open_first'] = array(
      '#title' => $this->t('Leave first fieldset open'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check to leave first fieldset open.'),
      '#default_value' => $this->options['open_first'],
      '#weight' => -48,
      '#states' => array(
        'invisible' => array(
          ':input[name="style_options[collapsed]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $options = array('' => $this->t('- None -'));
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $options += $field_labels;

    $form['title'] = array(
      '#type' => 'select',
      '#title' => $this->t('Details Title'),
      '#options' => $options,
      '#default_value' => $this->options['title'],
      '#description' => $this->t('Choose the title of details.'),
      '#weight' => -47,
    );

    $form['description'] = array(
      '#type' => 'select',
      '#title' => $this->t('Details Description'),
      '#options' => $options,
      '#default_value' => $this->options['description'],
      '#description' => $this->t('Optional details description.'),
      '#weight' => -46,
    );
  }

}
