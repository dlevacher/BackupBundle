<?php

namespace Itsis\BackupBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Class BackupSqlCommand
 *
 * Lets make a backup of the database
 *
 * @package Itsis\BackupBundle\Command
 * @author Maarek Joseph <josephmaarek@gmail.com>
 */
class BackupSqlCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName("backup:sql")
            ->addArgument('format', InputOption::VALUE_OPTIONAL, 'Filename date format', 'D')
            ->addArgument('prefix', InputOption::VALUE_OPTIONAL, 'Filename prefix', 'backup-')
            ->addOption('compress', 'c', InputOption::VALUE_NONE, 'Compress output', 'If set, the generated file will be compressed')
            ->addOption('bin', 'b', InputOption::VALUE_OPTIONAL, 'The path to binary mysqldump', 'mysqldump')
            ->setDescription("Create a backup file of the database in the backup directory")
			->setHelp("format accept php date function format : http://php.net/manual/en/function.date.php");
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException If the driver is not pdo_mysql
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        /** @var $connection Connection */

        $driver = $connection->getDriver()->getName();
        $bin = $input->getOption('bin');
        $database = $connection->getDatabase();
        $host = $connection->getHost();
        $port = $connection->getPort();
        $pass = $connection->getPassword();
        $username = $connection->getUsername();

        if ($driver != "pdo_mysql") {
            throw new \InvalidArgumentException('Le driver doit être pdo_mysql.');
        }

        $fs = new Filesystem();
        $dir = $this->getContainer()->getParameter("itsis_backup.dir");
        if (false === $fs->exists($dir)) {
            $fs->mkdir($dir);
        }

        $filename = $input->getArgument('prefix').date($input->getArgument('format')) . ".sql";
        $out = $dir . DIRECTORY_SEPARATOR . $filename;

        $opts = array(
            "-h {$host}",
            "-u {$username}",
            "-p{$pass}",
			'--opt',
			'--skip-triggers',
			'--add-drop-table',
			'--disable-keys',
			'--extended-insert',
        );
		// $opts[] = '--log-error='.$errorFile; //  --flush-logs --skip-lock-tables
        if (!empty($port)) {
            $opts[] = "-P {$port}";
        }

        $verboseOpt = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE ? "-v" : "";
        $command = $bin." " . implode(' ', $opts) . " {$database} {$verboseOpt} > {$out}";
        $output->writeln($command);
        $process = new Process($command);
        $process->run(function ($type, $buffer) use ($output, $out) {
            if ('err' === $type) {
                $output->write(sprintf('<error>%s</error>', $buffer));
            } else if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->write(sprintf('<info>%s</info>', $buffer));
            }
        });

        if ($process->isSuccessful() && $output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln("<info>" . file_get_contents($out) . "</info>");
        }
		
        if ($input->getOption('compress')) {
			$zip = new \ZipArchive();
			if ($zip->open("{$out}.zip", \ZIPARCHIVE::CREATE) === true) {
				$zip->addFile($out, basename($out));
				$zip->close();
				if (file_exists("{$out}.zip")) {
					if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
						$output->writeln(sprintf("<info>'{$database}' backup successfully saved in '{$out}.zip'</info>");
					@unlink($out);
				}
				else if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
					$output->writeln("<error>Error writing '{$database}' database to '{$out}.zip'</error>");
				}
			}
		}
		else if ($process->isSuccessful() && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("<info>'{$database}' backup successfully saved in '{$out}'</info>");
        }
		
    }
} 