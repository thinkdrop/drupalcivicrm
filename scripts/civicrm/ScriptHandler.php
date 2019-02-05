<?php

/**
 * @file
 * Contains \DrupalProject\civicrm\ScriptHandler.
 */

namespace DrupalProject\civicrm;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Drupal\Core\Archiver\ArchiveTar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ScriptHandler {

  const ASSET_EXTENSIONS = [
    'html',
    'js',
    'css',
    'svg',
    'png',
    'jpg',
    'jpeg',
    'ico',
    'gif',
    'woff',
    'woff2',
    'ttf',
    'eot',
    'swf',
  ];

  /**
   * Builds CiviCRM.
   *
   * @see https://www.mydropwizard.com/blog/how-install-civicrm-drupal-8-and-why-choose-it-over-pure-drupal-crm
   */
  public static function buildCivicrm(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $vendor_path = $composer->getConfig()->get('vendor-dir');
    $civicrm_core_path = "{$vendor_path}/civicrm/civicrm-core";

    $io->write("<info>Running bower for CiviCRM...</info>");
    $bower = (new Process("bower install", $civicrm_core_path))->mustRun();
    $io->write($bower->getOutput(), FALSE, IOInterface::VERBOSE);

    $filesystem = new Filesystem();

    $civicrm_version = static::getCivicrmVersion($composer);
    $civicrm_archive_url = "https://download.civicrm.org/civicrm-{$civicrm_version}-drupal.tar.gz";
    $civicrm_archive_file = tempnam(sys_get_temp_dir(), "drupal-civicrm-archive-");
    $civicrm_extract_path = tempnam(sys_get_temp_dir(), "drupal-civicrm-extract-");

    // Convert the extract path into a directory.
    $filesystem->remove($civicrm_extract_path);
    $filesystem->mkdir($civicrm_extract_path);

    try {
      $io->write("<info>Downloading CiviCRM release...</info>");
      file_put_contents($civicrm_archive_file, fopen($civicrm_archive_url, 'r'));

      $io->write("<info>Extracting CiviCRM release...</info>");
      (new ArchiveTar($civicrm_archive_file, "gz"))->extract($civicrm_extract_path);

      $io->write("<info>Copying extra files from CiviCRM release...</info>");

      $filesystem->mirror("{$civicrm_extract_path}/civicrm/packages", "{$civicrm_core_path}/packages");
      $filesystem->mirror("{$civicrm_extract_path}/civicrm/sql", "{$civicrm_core_path}/sql");

      file_put_contents("{$civicrm_core_path}/civicrm-version.php", str_replace('Drupal', 'Drupal8', file_get_contents("{$civicrm_extract_path}/civicrm/civicrm-version.php")));

      $simple_copy_list = [
        'civicrm.config.php',
        'CRM/Core/I18n/SchemaStructure.php',
        'install/langs.php',
      ];
      foreach ($simple_copy_list as $file) {
        $filesystem->copy("{$civicrm_extract_path}/civicrm/{$file}", "{$civicrm_core_path}/{$file}");
      }
    }
    finally {
      if (file_exists($civicrm_archive_file)) {
        unlink($civicrm_archive_file);
      }

      if (file_exists($civicrm_extract_path)) {
        static::removeDirectoryRecursively($civicrm_extract_path);
      }
    }

    $web_path = dirname(dirname(__DIR__)) . '/web/libraries/civicrm';
    $io->write("<info>Syncing CiviCRM web assets to /web/libraries/civicrm...</info>");
    static::mirrorWebAssets($civicrm_core_path, $web_path);
  }

  /**
   * Get the current CiviCRM version.
   *
   * @param \Composer\Composer $composer
   *   The composer object.
   *
   * @return string
   *   The version string.
   */
  protected static function getCivicrmVersion(Composer $composer) {
    /** @var \Composer\Repository\RepositoryManager $repository_manager */
    $repository_manager = $composer->getRepositoryManager();

    /** @var \Composer\Repository\RepositoryInterface $local_repository */
    $local_repository = $repository_manager->getLocalRepository();

    /** @var \Composer\Package\Package $package */
    foreach ($local_repository->getPackages() as $package) {
      if ($package->getName() == 'civicrm/civicrm-core') {
        return $package->getPrettyVersion();
      }
    }

    throw new \RuntimeException("Unable to determine CiviCRM version");
  }

  /**
   * Remove a directory recursively.
   *
   * @param string $dir
   *   The directory.
   */
  protected static function removeDirectoryRecursively($dir) {
    if (!file_exists($dir)) {
      return;
    }

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
      $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
      $todo($fileinfo->getRealPath());
    }

    rmdir($dir);
  }

  /**
   * Mirror web assets to destination inside the web root.
   *
   * @param string $source
   *   Path to the source directory.
   * @param string $destination
   *   Path to the destination directory.
   */
  protected static function mirrorWebAssets($source, $destination) {
    $filesystem = new Filesystem();
    $filesystem->mkdir($destination);

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS));

    /** @var \SplFileInfo $fileinfo */
    foreach ($files as $fileinfo) {
      if (!$fileinfo->isDir() && in_array($fileinfo->getExtension(), static::ASSET_EXTENSIONS)) {
        $destination_path = $destination . '/' . $filesystem->makePathRelative($fileinfo->getPath(), $source);
        if (!$filesystem->exists($destination_path)) {
          $filesystem->mkdir($destination_path);
        }
        $destination_file = $destination_path . $fileinfo->getFilename();
        $filesystem->copy($fileinfo->getRealPath(), $destination_file);
      }
    }

    static::removeDirectoryRecursively("{$destination}/tests");

    $filesystem->mirror("{$source}/extern", "{$destination}/extern");
    $filesystem->copy("{$source}/civicrm.config.php", "{$destination}/civicrm.config.php");

    $settings_location_php = <<<EOF
<?php

define('CIVICRM_CONFDIR', '../../../sites');
EOF;
    file_put_contents("{$destination}/settings_location.php", $settings_location_php);
  }

}
