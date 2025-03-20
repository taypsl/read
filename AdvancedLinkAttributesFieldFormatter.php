<?php

namespace Drupal\ala\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'AdvancedLinkFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ala",
 *   label = @Translation("Advanced Link Attributes"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class AdvancedLinkAttributesFieldFormatter extends LinkFormatter {


  /**
   * The token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor for AdvancedLinkAttributesFieldFormatter.
   *
   * @param Drupal\Core\Utility\Token $token
   *   The token.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(Token $token, AccountInterface $current_user) {
    $this->token = $token;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => 80,
      'ala_link_view_class' => 'element',
      'ala_link_view_icon' => 'inside',
      'ala_link_view_icon_position' => 'left',
      'ala_link_view_roles' => 'hide',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['ala_link_view_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Class Position'),
      '#options' => [
        'element' => $this->t('Link Element'),
        'parent' => $this->t('Parent Element'),
      ],
      '#default_value' => $this->getSetting('ala_link_view_class'),
    ];
    $elements['ala_link_view_icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon Position'),
      '#options' => [
        'inside' => $this->t('As tag "i" Inside Element'),
        'class' => $this->t('As a class'),
        'data' => $this->t('As data attr'),
      ],
      '#default_value' => $this->getSetting('ala_link_view_icon'),
    ];
    $elements['ala_link_view_icon_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon Position to Text'),
      '#options' => [
        'left' => $this->t('To the left of text'),
        'right' => $this->t('To the right of text'),
      ],
      '#default_value' => $this->getSetting('ala_link_view_icon_position'),
    ];
    $elements['ala_link_view_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Role Visibility'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'hidden' => $this->t('Visually Hidden'),
      ],
      '#default_value' => $this->getSetting('ala_link_view_roles'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        $link_title = $this->token
          ->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      $options = $url->getOptions();
      $options['parent_classes'] = [];

      if (isset($options['attributes']) && is_array($options['attributes'])) {
        foreach ($options['attributes'] as $key => $attribute) {
          if (empty($attribute)) {
            unset($options['attributes'][$key]);
          }
        }
      }

      if (empty($options['attributes']['title'])) {
        $options['attributes']['title'] = trim(Html::escape($link_title));
      }

      if (!empty($options['icon'])) {
        switch ($settings['ala_link_view_icon']) {
          case "inside":
            switch ($settings['ala_link_view_icon_position']) {
              case "left":
                $link_title = Markup::create('<i class="' . $options['icon'] . '"></i>' . $link_title);
                break;

              case "right":
                $link_title = Markup::create($link_title . '<i class="' . $options['icon'] . '"></i>');
                break;
            }
            break;

          case "class":
            $options['attributes']['class'][] = $options['icon'];
            break;

        }
      }
      else {
        unset($options['attributes']['data-icon']);
      }

      if (!empty($options['class'])) {
        switch ($settings['ala_link_view_class']) {
          case "parent":
            $options['parent_classes'] = $options['class'];
            break;

          case "element":
            $options['attributes']['class'][] = $options['class'];
            break;

        }
      }

      $styles = [];
      if (!empty($options['color'])) {
        $styles[] = 'color:' . $options['color'];
      }

      if (!empty($options['bgcolor'])) {
        $styles[] = 'background-color:' . $options['bgcolor'];
      }

      if (!empty($styles)) {
        $options['attributes']['style'] = implode(';', $styles);
      }

      $url->setOptions($options);

      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = [
          '#plain_text' => $link_title,
        ];

        if (!empty($item->_attributes)) {
          $content = str_replace('internal:/', '', $item->uri);
          $item->_attributes += ['content' => $content];
        }
      }
      else {

        $element[$delta] = [
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $options,
        ];
        $element[$delta]['#url'] = $url;

        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += ['attributes' => []];
          $element[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }

      if (!empty($options['roles'])) {
        $current_user = $this->currentUser;
        $roles = $current_user->getRoles();
        $link_roles = array_keys($options['roles']);
        if (!in_array('all', $link_roles)) {
          if (count(array_intersect($link_roles, $roles)) == 0) {
            unset($element[$delta]);
          }
        }
      }
    }

    return $element;
  }

}
