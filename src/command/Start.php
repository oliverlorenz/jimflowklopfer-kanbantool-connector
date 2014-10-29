<?php
/**
 * @author Oliver Lorenz <oliver.lorenz@project-collins.com>
 * @since 2014-10-18
 */

namespace command;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use system\Configuration;
use \Curl\Curl;

class Start extends Command
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('connector:start')
            ->setDescription('create task')
        ;
    }

    protected function getConfigPaths()
    {
        return array(
            getcwd() . '/src/config',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $fileName = 'main';
        $this->config = new Configuration(
            $this->getConfigPaths(),
            $fileName
        );

        if($this->config->getValue('jimFlowKlopfer')['run'] === true) {
            $this->cleanUpPhotoDirectoryTillOnlyOneImageLeft();
            $this->runJimFlowKlopfer();
        } else {
            $this->output->writeln('skip jimFlowKlopfer because deactivated in config');
        }

        $boardId = $this->config->getValue('board')['boardId'];

        $jsonDirectory = $this->config->getValue('jimFlowKlopfer')['jsonDirectory'];
        $pattern = $jsonDirectory . '*_' . $boardId . '.json';

        foreach (glob($pattern) as $filePath) {
            $this->output->writeln($filePath);
            $data = json_decode(file_get_contents($filePath), true);
            if (isset($data['board']['info']['board_id']) && $data['board']['info']['board_id'] == $boardId) {
                foreach ($data['board']['informations'] as $ticketData) {
                    $klopferColumnId = $ticketData['column'];
                    $mappedColumnId = $this->getMappedColumnId($klopferColumnId);

                    $regex = $this->config->getValue('board')['ticketRegex'];
                    if (!empty($regex) && preg_match($regex, $ticketData['data'], $matches)) {
                        $ticketId = $matches[1];
                    } else {
                        $ticketId = $ticketData['data'];
                    }

                    $this->moveTicket($ticketId, $mappedColumnId);
                }
            }
            $this->output->writeln('delete json file "' . $filePath . '"');
            unlink($filePath);
        }
    }

    /**
     * @param string $photoDirectory
     * @return array
     */
    protected function getImagesSortedByModifyTimestampAsc($photoDirectory)
    {
        $files = glob($photoDirectory . '*.*' );
        array_multisort(
            array_map( 'filemtime', $files ),
            SORT_NUMERIC,
            SORT_ASC,
            $files
        );
        return $files;
    }

    protected function getUrl($domain, $boardId, $apiToken)
    {
        return 'https://' . $domain . '.kanbantool.com/api/v1/boards/' . $boardId . '/tasks.xml?api_token=' . $apiToken;
    }

    protected function getMappedColumnId($klopferColumnId)
    {
        if (isset($this->config->getValue('board')['columns'][$klopferColumnId])) {
            return $this->config->getValue('board')['columns'][$klopferColumnId];
        }
    }

    protected function moveTicket($ticketId, $mappedTargetColumnId)
    {
        $rawCommand = $this->config->getValue('board')['commands']['move'];
        $fullCommand = sprintf($rawCommand, $ticketId, $mappedTargetColumnId);
        system($fullCommand);
        $this->output->writeLn('move Ticket "' . $ticketId . '" to column "' . $mappedTargetColumnId . '"');
    }

    /**
     * @return array
     */
    protected function cleanUpPhotoDirectoryTillOnlyOneImageLeft()
    {
        $photoDirectory = $this->config->getValue('jimFlowKlopfer')['photoDirectory'];

        $this->output->writeln('start searching for images in "' . $photoDirectory . '"');

        $filePathList = $this->getImagesSortedByModifyTimestampAsc($photoDirectory);
        $fileToProcess = array_shift($filePathList);
        $this->output->writeln('selected file for processing: "' . $fileToProcess . '"');

        if (!empty($filePathList)) {
            foreach ($filePathList as $filePath) {
                $this->output->writeln('delete file because its to old! "' . $filePath . '"');
                unlink($filePath);
            }
            return $filePathList;
        }
        return $filePathList;
    }

    protected function runJimFlowKlopfer()
    {
        $this->output->writeLn('run jimFlowKlopfer');
        $command = $this->config->getValue('jimFlowKlopfer')['command'];
        $photoDirectory = $this->config->getValue('jimFlowKlopfer')['photoDirectory'];
        $jsonDirectory = $this->config->getValue('jimFlowKlopfer')['jsonDirectory'];
        $fullCommand = sprintf($command, $photoDirectory, $jsonDirectory);
        $this->output->writeLn($fullCommand);

        exec($fullCommand);
    }
}