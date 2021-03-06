<?php

/**
 * @file
 * Drush hook implementations for the Composer Manager module.
 */

/**
 * Implements hook_drush_command().
 */
function composer_manager_drush_command() {
  $items = array();

  $items['composer-manager'] = array(
    'description' => 'Executes a composer command in the directory containing the composer.json file.',
    'allow-additional-options' => TRUE,
    'aliases' => array('composer-execute'),
    'arguments' => array(
      'command' => 'The command to run through the Composer Manager composer.json file. Defaults to "install".',
    ),
  );

  $items['composer-json-rebuild'] = array(
    'description' => 'Rebuilds the consolidated composer.json file.',
    'allow-additional-options' => TRUE,
    'aliases' => array(
      'composer-rebuild',
      'composer-rebuild-file',
    ),
  );

  return $items;
}

/**
 * Executes a Composer command on Composer Manager's composer.json file.
 *
 * @param $command
 *   Which command to execute on Composer Manager's composer.json file.
 */
function drush_composer_manager($command = 'install') {
  $uri = Drupal::config('composer_manager.settings')->get('file_dir');
  if (!$dir = drupal_realpath($uri)) {
    return drush_set_error(dt('Error resolving path: @uri', array('@uri' => $uri)));
  }

  // Check to see if the composer.json file is available.
  if (!file_exists($dir . '/composer.json')) {
    // Ask the user would like to install Drush Composer.
    if (drush_confirm(dt('The composer.json file is missing. Would you like to re-build it?'))) {
      if (drush_invoke('composer-rebuild-file') === FALSE) {
        return FALSE;
      }
    }
    else {
      return drush_set_error(dt('Missing composer.json file: Run `drush composer-rebuild-file`.'));
    }
  }

  // Check if Drush Composer is available.
  if (!drush_is_command('composer')) {
    // Ask the user would like to install Drush Composer.
    if (!drush_confirm(dt('Download and install the Drush Composer extension?'))) {
      drush_print(dt('Installation of the Drush Composer extension was aborted.'));
      return TRUE;
    }

    // Download the Drush extension.
    if (drush_invoke('dl', array('composer-8.x-1.x')) === FALSE) {
      return drush_set_error(dt('Failed installing the Drush Composer extension. Install it manually by following the instructions on the project page: http://drupal.org/project/composer'));
    }

    // Clear the caches by reloading the available command files.
    _drush_find_commandfiles(DRUSH_BOOTSTRAP_DRUSH);
  }
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $dir = str_replace('\\', '/', $dir);
  }
  // Generate the command arguments to pass to Drush Composer.
  $drush_arguments = drush_get_arguments();
  if ($drush_arguments && $drush_arguments[0] === 'composer-manager') {
    $arguments = $drush_arguments;
    array_shift($arguments); // exclude drush main command, composer-manager
    $arguments[] = '--working-dir=' . escapeshellcmd($dir);
  }
  else {
    $arguments = array(
      $command,
      // The use of escapeshellcmd rather than escapeshellarg is necessary to
      // avoid enclosing the value in quotes, which is incompatible with the way
      // Composer validates that the working directory exists.
      '--working-dir=' . escapeshellcmd($dir),
    );
  }

  $composer_result = drush_invoke('composer', $arguments);
  // Allow modules to perform post-composer install tasks.
  if ($command == 'install' || $command == 'update') {
    Drupal::moduleHandler()->invokeAll('composer_dependencies_install');
  }

  return $composer_result;
}

/**
 * Rebuilds the consolidated composer.json file.
 */
function drush_composer_manager_composer_json_rebuild() {
  try {
    Drupal::service('composer_manager.packages')->writeComposerJsonFile();
    drush_log(dt('Completed building composer.json file.'), 'ok');
    return TRUE;
  }
  catch (\Exception $e) {
    return drush_set_error($e->getMessage());
  }
}
