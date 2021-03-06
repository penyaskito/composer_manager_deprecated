<?php

/**
 * @file
 * Contains \Drupal\composer_manager\ComposerPackagesInterface.
 */

namespace Drupal\composer_manager;

interface ComposerPackagesInterface {

  /**
   * @return \Drupal\composer_manager\FilesystemInterface
   */
  public function getFilesystem();

  /**
   * @return \Drupal\composer_manager\ComposerManagerInterface
   */
  public function getManager();

  /**
   * Reads installed package versions from the composer.lock file.
   *
   * @return array
   *   An associative array of package version information.
   *
   * @throws \RuntimeException
   */
  public function getInstalled();

  /**
   * Returns the packages, versions, and the modules that require them in the
   * composer.json files contained in contributed modules.
   *
   * @return array
   */
  public function getRequired();

  /**
   * Returns each installed packages dependents.
   *
   * @return array
   *   An associative array of installed packages to their dependents.
   *
   * @throws \RuntimeException
   */
  public function getDependencies();

  /**
   * Returns a list of packages that need to be installed.
   *
   * @return array
   */
  public function getInstallRequired();

  /**
   * Writes the consolidated composer.json file for all modules that require
   * third-party packages managed by Composer.
   *
   * @return int
   *
   * @throws \RuntimeException
   */
  public function writeComposerJsonFile();

  /**
   * Returns TRUE if at least one passed modules has a composer.json file,
   * which flags that the list of packages managed by Composer Manager have
   * changed.
   *
   * @param array $modules
   *   The list of modules being scanned for composer.json files, usually a list
   *   of modules that were installed or uninstalled.
   *
   * @return bool
   */
  public function haveChanges(array $modules);
}
