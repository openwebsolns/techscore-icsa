<?php
/*
 * Add all the necessary files, etc.
 *
 * @author Dayan Paez
 * @version 2015-11-08
 */

$base = array_shift($argv);
function usage($error, $exit_status = 1) {
  global $base;
  if ($error !== null) {
    echo $error, "\n\n";
  }
  echo "usage: $base --root <dir> --user <id> [filename1, ...]

  --root <dir>   Directory where Techscore is installed.
  --user <id>    ID or e-mail of the user to use.

  --help, -h     Print this message and exit.
";
  exit($exit_status);
}

// Options
$techscoreRoot = null;
$userId = null;
$filenames = array();
while (count($argv) > 0) {
  $opt = array_shift($argv);
  switch ($opt) {
  case '--help':
  case '-h':
    usage(null, 0);
    break;

  case '--root':
    if (count($argv) == 0) {
      usage("Missing argument for --root.");
    }
    $techscoreRoot = array_shift($argv);
    if (!is_dir($techscoreRoot)) {
      usage("$techscoreRoot is not a directory.");
    }
    break;

  case '--user':
    if (count($argv) == 0) {
      usage("Missing argument for --root.");
    }
    $userId = array_shift($argv);
    break;

  default:
    $filenames[] = $opt;
  }
}
if ($techscoreRoot == null) {
  usage("Missing Techscore root directory.");
}
if ($userId == null) {
  usage("Missing user ID or email.");
}


require_once($techscoreRoot . '/lib/conf.php');
$user = DB::getAccount($userId);
if ($user === null) {
  $user = DB::getAccountByEmail($userId);
}
if ($user === null) {
  usage(sprintf("No user found with ID/email \"%s\".", $userId));
}

class PA {
  const E = 'e';
  const I = 'i';
  const S = 's';
  public function __construct($message) {
  }
}
class Session {
  public static function pa($message, $type = null) {
  }
}

$options = array();
$_FILES = array();
$_FILES['file'] = array(
  'name' => array(),
  'tmp_name' => array(),
  'error' => array(),
  'size' => array(),
  'type' => array(),
);
foreach (scandir(__DIR__ . '/res') as $dir) {
  if ($dir != '.' && $dir != '..') {
    $dirname = __DIR__ . '/res/' . $dir;
    foreach (scandir($dirname) as $file) {
      if ($file != '.' && $file != '..') {
        if (count($filenames) == 0 || in_array($file, $filenames)) {
          $filename = $dirname . '/' . $file;
          $_FILES['file']['name'][] = $file;
          $_FILES['file']['tmp_name'][] = $filename;
          $_FILES['file']['error'][] = 0;
          $_FILES['file']['size'][] = filesize($filename);
          $_FILES['file']['type'][] = null;

          if ($dir == 'js-async') {
            $options[$file] = 'auto-async';
          }
          if ($dir == 'js-sync') {
            $options[$file] = 'auto-sync';
          }
        }
      }
    }
  }
}

$toDelete = array();
if (count($filenames) == 0) {
  foreach (DB::getAll(DB::T(DB::PUB_FILE_SUMMARY)) as $file) {
    $toDelete[] = $file->id;
  }
}

require_once('users/admin/PublicFilesManagement.php');
try {
  $P = new PublicFilesManagement($user);
  $P->process(
    array(
      'upload' => "Techscore-ICSA",
      'delete' => $toDelete,
    )
  );

  if (count($options) > 0) {
    $_FILES = array();
    $P->process(
      array(
        'upload' => "Techscore-ICSA",
        'options' => $options,
      )
    );
  }
}
catch (PermissionException $e) {
  usage($e->getMessage());
}