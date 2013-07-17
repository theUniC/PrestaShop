<?php

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Console extends Application
{
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $refactorClassesCommand = new Command('refactor:classes:psr0');
        $refactorClassesCommand
            ->setCode(function(InputInterface $input, OutputInterface $output) {
                $finder = Finder::create();
                $finder
                    ->files()
                    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'classes')
                    ->name('*.php')
                ;

                if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'src')) {
                    mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'src');
                }

                /** @var SplFileInfo $file */
                foreach ($finder as $file) {
                    if (in_array($file->getFilename(), array('index.php', '.htaccess'))) {
                        continue;
                    }

                    $output->writeln(sprintf('<info>Refactoring file <comment>%s</comment></info>', $file->getRealPath()));
                    $className = str_replace('.php', '', pathinfo($file->getRealPath(), PATHINFO_FILENAME));
                    $namespace = 'Prestashop';

                    if ('/datos/workspace/PrestaShop/classes' != dirname($file->getRealPath())) {
                        $namespace .= '\\' . str_replace(' ', '\\', ucwords(str_replace(DIRECTORY_SEPARATOR, ' ', str_replace('/datos/workspace/PrestaShop/classes/', '', dirname($file->getRealPath())))));
                    }

                    $content = $file->getContents();
                    $content = str_replace('class ' . $className . 'Core', 'class ' . $className, $content);
                    $content = str_replace('<?php', '<?php

namespace ' . $namespace . ';
', $content);
                    $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR . $className . '.php';

                    if (!is_dir(dirname($filePath))) {
                        mkdir(dirname($filePath), 0777, true);
                    }

                    file_put_contents($filePath, $content);
                }
            })
        ;

        $commands[] = $refactorClassesCommand;

        $checkDuplicateClassNamesCommand = new Command('refactor:classes:check-dupes');
        $checkDuplicateClassNamesCommand->setCode(function(InputInterface $input, OutputInterface $output) {
            $finder = Finder::create();
            $finder
                ->files()
                ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src')
                ->name('*.php')
            ;

            $classes = array();

            $output->writeln('<info>Checking duplicate class names</info>');

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $className = $file->getBasename('.php');
                if (!isset($classes[$className])) {
                    $classes[$className] = 0;
                } else {
                    $classes[$className]++;
                }
            }

            foreach ($classes as $className => $times) {
                if ($times > 0) {
                    $output->writeln(sprintf('<info>The class <comment>%s</comment> has been found %d times</info>', $className, $times));
                }
            }
        });

        $commands[] = $checkDuplicateClassNamesCommand;

        $refactorControllersCommand = new Command('refactor:controllers:psr0');
        $refactorControllersCommand->setCode(function(InputInterface $input, OutputInterface $output) {
            $finder = Finder::create();
            $finder
                ->files()
                ->in(__DIR__ . DIRECTORY_SEPARATOR . 'controllers')
                ->name('*.php')
            ;

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                if (in_array($file->getFilename(), array('index.php', '.htaccess'))) {
                    continue;
                }

                $output->writeln(sprintf('<info>Refactoring file <comment>%s</comment></info>', $file->getRealPath()));
                $className = str_replace('.php', '', pathinfo($file->getRealPath(), PATHINFO_FILENAME));
                $namespace = 'Prestashop';
                $namespace .= '\\' . str_replace(' ', '\\', ucwords(str_replace(DIRECTORY_SEPARATOR, ' ', str_replace('controllers', 'controller', str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', dirname($file->getRealPath()))))));

                $content = $file->getContents();
                $content = str_replace('<?php', '<?php

namespace ' . $namespace . ';
', $content);
                $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR . $className . '.php';

                if (!is_dir(dirname($filePath))) {
                    mkdir(dirname($filePath), 0777, true);
                }

                file_put_contents($filePath, $content);
            }
        });

        $commands[] = $refactorControllersCommand;

        return $commands;
    }
}

$console = new Console();
$console->run();