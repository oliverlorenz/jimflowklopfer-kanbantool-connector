<?php
/**
 * @author Oliver Lorenz <oliver.lorenz@project-collins.com>
 * @since 2014-10-18
 */

require_once dirname(__FILE__) . "/vendor/autoload.php";

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\FileLocator;

// mock the Kernel or create one depending on your needs
try {
    $application = new Application();
    $output = new ConsoleOutput();
    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

    $application->add(new \command\Start());
    $application->run(null, $output);
} catch (\Exception $exception) {
    throw $exception; //
}