<?php

require dirname(__DIR__) . '/StaffUtils/vendor/autoload.php';

$file_phar = 'StaffUtils.phar';

if (file_exists($file_phar)) {
    echo "Phar file already exists!";

    echo PHP_EOL;

    echo "overwriting...";

    Phar::unlinkArchive($file_phar);
}

$files = [];
$dir = getcwd() . DIRECTORY_SEPARATOR;

$exclusions = [".idea", ".gitignore", "composer.json", 'composer.phar', "composer.lock", "make-phar.php", ".git", "vendor"];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $path => $file) {
    $bool = true;
    foreach ($exclusions as $exclusion) {
        if ($exclusion === 'vendor' && (str_contains($path, 'predis') || str_contains($path, 'riorizkyrainey') || str_contains($path, 'autoload.php') || str_contains($path, 'composer') || str_contains($path, 'pocketmine') || str_contains($path, 'phpstan'))) {
            continue;
        }

        if (str_contains($path, $exclusion)) {
            $bool = false;

            break;
        }
    }

    if (!$bool) {
        continue;
    }

    if (!$file->isFile()) {
        continue;
    }

    $files[str_replace($dir, "", $path)] = $path;
}

echo "Compressing..." . PHP_EOL;

$phar = new Phar($file_phar);
$phar->startBuffering();
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->buildFromIterator(new ArrayIterator($files));
$phar->setStub('<?php echo "thatsmybaby"; __HALT_COMPILER();');
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
echo "end." . PHP_EOL;