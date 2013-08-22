<?php

require 'vendor/autoload.php';

use Prestashop\Tools;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\ProcessBuilder;

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

        $fixClassReferences = new Command('refactor:fix-class-references');
        $fixClassReferences->setCode(function(InputInterface $input, OutputInterface $output) {
            $finder = Finder::create();
            $finder
                ->files()
                ->in(__DIR__ . DIRECTORY_SEPARATOR . 'modules')
                ->name('*.php')
                ->notName('index.php')
            ;

            $parser = new PHPParser_Parser(new PHPParser_Lexer());
            $printer = new PHPParser_PrettyPrinter_Default();
            ini_set('xdebug.max_nesting_level', 2000);

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $output->writeln(sprintf('<info>Traversing <comment>%s</comment></info>', $file->getRealPath()));
                $nodes = $parser->parse($file->getContents());

                // If already has a "use", skip
                if ($nodes[0]->stmts[0] instanceof PHPParser_Node_Stmt_Use) {
                    continue;
                }

                $traverser = new PHPParser_NodeTraverser();
                $traverser->addVisitor($classReferenceVisitor = new ClassReferenceVisitor());
                $nodes = $traverser->traverse($nodes);
                $filePathParts = explode(DIRECTORY_SEPARATOR, $file->getRealPath());
                $currentClassName = basename($filePathParts[count($filePathParts) - 1], '.php');

                if (count($classReferenceVisitor->getUses())) {
                    $uses = array();
                    foreach ($classReferenceVisitor->getUses() as $use) {

                        if ($currentClassName == $use) {
                            continue;
                        }

                        $parts = explode('\\', $use);
                        // Find the right namespace
                        $namespaceFinder = Finder::create();
                        $namespaceFinder
                            ->files()
                            ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src')
                            ->name(array_pop($parts) . '.php');
                        ;

                        $path = dirname(key(iterator_to_array($namespaceFinder)));
                        $path = str_replace(__DIR__ . DIRECTORY_SEPARATOR . 'src/', '', $path);
                        $path = str_replace(DIRECTORY_SEPARATOR, '\\', $path);

                        $uses[] = new PHPParser_Node_Stmt_Use(
                            array(
                                new PHPParser_Node_Stmt_UseUse(
                                    new PHPParser_Node_Name($path . '\\' . $use)
                                )
                            )
                        );
                    }

                    file_put_contents($file->getRealPath(), '<?php

' . $printer->prettyPrint(array_merge($uses, $nodes)));
                }
            }
        });

        $commands[] = $fixClassReferences;

        $clearCacheSmarty = new Command('smarty:clear:cache');
        $clearCacheSmarty->setCode(function(InputInterface $input, OutputInterface $output) {
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '/console.php';

            require __DIR__ . '/config/config.inc.php';

            // That's a totally mess, I know
            /** @var Smarty $smarty */
            global $smarty;

            $smarty->clearAllCache();
        });

        $commands[] = $clearCacheSmarty;

        $clearCompiledSmarty = new Command('smarty:clear:compiled');
        $clearCompiledSmarty->setCode(function(InputInterface $input, OutputInterface $output) {
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '/console.php';

            require __DIR__ . '/config/config.inc.php';

            // That's a totally mess, I know
            /** @var Smarty $smarty */
            global $smarty;

            $smarty->clearCompiledTemplate();
        });

        $commands[] = $clearCompiledSmarty;

        $htAccessCommand = new Command('prestashop:generate-htaccess');
        $htAccessCommand->setCode(function(InputInterface $input, OutputInterface $output) {
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '/console.php';

            require __DIR__ . '/config/config.inc.php';

            Tools::generateHtaccess();
        });

        $commands[] = $htAccessCommand;

        return $commands;
    }
}

class ClassReferenceVisitor extends PHPParser_NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $uses = array();

    public function leaveNode(PHPParser_Node $node)
    {
        if (($node instanceof PHPParser_Node_Expr_New
            || $node instanceof PHPParser_Node_Expr_StaticCall
            || $node instanceof PHPParser_Node_Expr_ClassConstFetch)
            && $node->class instanceof PHPParser_Node_Name
        ) {
            if (!in_array($className = $node->class->toString(), array('static', 'self', 'parent'))) {
                $this->uses[] = $className;
            }
        }

        if ($node instanceof PHPParser_Node_Stmt_Class && null !== $node->extends) {
            $this->uses[] = $node->extends->toString();
        }
    }

    /**
     * @return array
     */
    public function getUses()
    {
        return array_unique($this->uses);
    }
}

$console = new Console();
$console->run();