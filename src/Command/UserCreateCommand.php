<?php

/**
 * @file
 * Contains \Drupal\Console\Command\UserCreateCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

class UserCreateCommand extends ContainerAwareCommand {

  /**
   * @var String Temporal value of current argument.
   */
  protected $currentArgument;

  /**
   * Retrieve current argument value.
   * @return String
   *   Argument value.
   */
  public function getCurrentArgument() {
    return $this->currentArgument;
  }

  /**
   * Define current argument value.
   * @param String $currentArgument
   *   Current argument value.
   */
  public function setCurrentArgument($currentArgument = NULL) {
    $this->currentArgument = $currentArgument;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('user:create')
        ->setDescription($this->trans('commands.user.create.description'))
        ->setHelp($this->trans('commands.user.create.help'))
        ->addArgument('name', InputArgument::REQUIRED, $this->trans('commands.user.create.options.name'))
        ->addArgument('pass', InputArgument::REQUIRED, $this->trans('commands.user.create.options.pass'))
        ->addArgument('mail', InputArgument::REQUIRED, $this->trans('commands.user.create.options.mail'));
  }

  /**
   * Validate value from current argument.
   */
  public function validateQuestions($value) {

    // Skip validation if current argument is not defined.
    if(!$arg = $this->getCurrentArgument()) {
      return false;
    }

    // Remove white space of current value.
    $value = trim($value);

    // Value cannot be empty.
    if(empty($value)) {

      $option = $this->trans("commands.user.create.options.{$arg}");
      $message = sprintf($this->trans('commands.user.create.errors.required'), $option);
      throw new \InvalidArgumentException($message);
    }

    // If current argument is mail then verify email address.
    if($arg == 'mail') {

      // Email Validator service.
      $emailValidator = \Drupal::service('email.validator');

      // Throw exception when value is not a correct email address.
      if(!$emailValidator->isValid($value)) {

        $message = $this->trans('commands.user.create.errors.mail');
        throw new \InvalidArgumentException($message);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // Define arguments to retrieve.
    $params = ['name', 'pass', 'mail'];
    // Walkthrough all arguments.
    foreach ($params as $arg) {

      // Retrieve current argument.
      if(!$value = $input->getArgument($arg)) {

        // Define current argument.
        $this->setCurrentArgument($arg);

        // If it was not found then execute dialog to ask for it.
        $dialog = $this->getDialogHelper();
        // Create question based on argument.
        $question = $this->trans("commands.user.create.questions.{$arg}");
        // Ask and validate current argument value.
        $value = $dialog->askAndValidate($output, $dialog->getQuestion($question), array($this, 'validateQuestions'));
      }
      // Define argument retrieved.
      $input->setArgument($arg, $value);
    }

    // Set current argument as NULL.
    $this->setCurrentArgument();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Define values based on arguments to retrieve.
    $values = ['status' => TRUE];
    $params = ['name', 'pass', 'mail'];
    // Walkthrough all arguments.
    foreach ($params as $arg) {

      // Retrieve value from argument.
      $value = $input->getArgument($arg);
      $values[$arg] = $value;
    }

    // Add authenticated role by default.
    $values['roles'] = array(RoleInterface::AUTHENTICATED_ID);
    // Create new entity user.
    $account = User::create($values);
    $account->save();

    // Check if account was created successfully.
    if (!$account->id()){
      $message = $this->trans("commands.user.create.errors.runtime");
      throw new \InvalidArgumentException($message);
    }

    // Command executed successful.
    $this->getMessageHelper()->addSuccessMessage(
      sprintf(
        $this->trans('commands.user.create.messages.successful'),
        $account->id()
      )
    );
  }
}
