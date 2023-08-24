<?php

namespace Drupal\registration_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Student Form
 */
class AuthorRegistrationForm extends FormBase
{

	/**
	 * The mail manager service.
	 *
	 * @var \Drupal\Core\Mail\MailManagerInterface
	 */
	protected $mailManager;

	/**
	 * Constructs an AuthorForm object.
	 *
	 * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
	 *   The mail manager service.
	 */
	public function __construct(MailManagerInterface $mail_manager)
	{
		$this->mailManager = $mail_manager;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container)
	{
		return new static(
			$container->get('plugin.manager.mail')
		);
	}

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

		$form['blogger_role'] = [
			'#type' => 'radios',
			'#title' => $this->t('Select Role'),
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
	public function validateForm(array &$form, FormStateInterface $form_state)
	{

		$email = $form_state->getValue('email');
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$form_state->setErrorByName('email', $this->t('Invalid email address.'));
		}


		$selected_role = $form_state->getValue('blogger_role');

		if (empty($selected_role)) {
			$form_state->setErrorByName('blogger_role', $this->t('Please select a role.'));
		} else {
			$valid_roles = ['blogger', 'guest_blogger'];

			if (!in_array($selected_role, $valid_roles)) {
				$form_state->setErrorByName('blogger_role', $this->t('Invalid role selected.'));
			}
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state)
	{

		$values = $form_state->getValues();
		$user = User::create();
		$user->setPassword($values['password']);
		$user->enforceIsNew();
		$user->setEmail($values['email']);
		$user->addRole($values['blogger_role']);
		$user->setUsername($values['full_name']);
		$user->set('field_full_name', $values['full_name']);
		$user->set('field_role', $values['blogger_role']);
		$user->block();
		$user->save();

		$params = [
			'subject' => 'New user registration',
			'body' => '<p>A new user has submitted the registration form. Please review and approve.</p>',
		];
		$this->mailManager->mail('registration_form', 'notification', 'taskphp2@gmail.com', LanguageInterface::LANGCODE_DEFAULT, $params, NULL, TRUE);

		$params = [
			'subject' => 'Thank you for your submission',
			'body' => '<p>Thank you for submitting the registration form. We will get back to you soon.</p>' .
			'<p>Your details:</p>' .
			'<p>Full Name: ' . $values['full_name'] . '</p>' .
			'<p>Email Address: ' . $values['email'] . '</p>' .
			'<p>Your Role: ' . $values['blogger_role'] . '</p>' .
			'<p>User ID: ' . $user->id() . '</p>',
		];
		$this->mailManager->mail('registration_form', 'notification', $values['email'], LanguageInterface::LANGCODE_DEFAULT, $params, NULL, TRUE);

	}

}