<?php

namespace Drupal\author_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides the Author Form.
 */
class AuthorForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'author_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Define form fields
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Role'),
      '#options' => [
        'blogger' => $this->t('Blogger'),
        'guest_blogger' => $this->t('Guest Blogger'),
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Get form values
    $values = $form_state->getValues();

    // Create new user
    $user = User::create();
    $user->setPassword($values['password']);
    $user->enforceIsNew();
    $user->setEmail($values['email']);
    $user->setUsername($values['full_name']);
    $user->addRole($values['role']);
    $user->block();
    $user->save();

    // Send notification to admin
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'author_form';
    $key = 'admin_notification';
    $to = 'punitpradhan01@gmail.com';
    $params['message'] = 'A new user has submitted the form: ' . $values['full_name'];
    $params['user'] = $user;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      \Drupal::messenger()->addMessage($this->t('Problem in sending message.'));
    } else {
      \Drupal::messenger()->addMessage($this->t('Your message has been sent.'));
    }

    // Send thank you email to user
    $key = 'user_thank_you';
    $to = $values['email'];
    $params['message'] = 'Thank you for your submission. We will get back to you soon.';
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      \Drupal::messenger()->addMessage($this->t('Problem in sending message.'));
    } else {
      \Drupal::messenger()->addMessage($this->t('Your message has been sent.'));
    }
  }
}