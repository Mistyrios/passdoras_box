<?php

namespace App\Command;

use App\Entity\Identifier;
use App\Service\IdentifierService;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Key;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fill-database',
    description: 'Fill the database with the data in the data folder',
)]
class FillDatabaseCommand extends Command
{
    private const DATA_PATH = __DIR__.'/../../data';

    public function __construct(
        private readonly IdentifierService $identifierService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $io->info('Start filling the database');

            // Get all files in the data directory
            $files = scandir(self::DATA_PATH);

            // Filter out hidden files (e.g., "." and "..")
            $files = array_filter($files, static fn ($file) => !in_array($file, ['.', '..']));

            // Progress bar for the files
            $fileProgressBar = $io->createProgressBar(count($files));
            $fileProgressBar->setFormat('Processing files: %current%/%max% [%bar%] %percent:3s%%');
            $fileProgressBar->start();

            foreach ($files as $file) {
                $fileProgressBar->advance();

                // Load the JSON file
                $fileContents = file_get_contents(self::DATA_PATH.'/'.$file);

                if (false === $fileContents) {
                    throw new \RuntimeException('Could not read file '.$file);
                }

                $data = json_decode($fileContents, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($data)) {
                    throw new \RuntimeException('Could not decode JSON file '.$file);
                }

                foreach ($data as $identifierData) {
                    if (!is_array($identifierData)) {
                        continue;
                    }

                    if (
                        '' === $identifierData['pw']
                        || '' === $identifierData['label']
                        || '' === $identifierData['login']
                        || '?' === $identifierData['pw']
                        || '?' === $identifierData['login']) {
                        continue;
                    }

                    $identifiers = $this->identifierService->getIndentifiersByLabelAndLogin(
                        $identifierData['label'],
                        $identifierData['login']
                    );

                    $cryptoKey = $_ENV['CRYPTO_KEY'] ?? null;

                    if (!is_string($cryptoKey) || empty(trim($cryptoKey))) {
                        throw new BadFormatException('Invalid crypto key');
                    }

                    foreach ($identifiers as $identifier) {
                        if (!$identifier instanceof Identifier) {
                            continue;
                        }

                        if (Crypto::decrypt($identifier->getPassword(), Key::loadFromAsciiSafeString($cryptoKey)) === $identifierData['pw']) {
                            continue 2;
                        }
                    }

                    $identifier = new Identifier();
                    $label = mb_convert_encoding($identifierData['label'], 'UTF-8', 'ISO-8859-1');

                    if (!is_string($label)) {
                        throw new \RuntimeException('Could not convert label to string');
                    }

                    $identifier->setLabel($label);
                    $identifier->setLogin($identifierData['login']);
                    $password = $identifierData['pw'];

                    if (!is_string($password)) {
                        throw new \RuntimeException('Could not convert password to string');
                    }

                    $identifier->setPassword(Crypto::encrypt($password, Key::loadFromAsciiSafeString($cryptoKey)));
                    $identifier->setLink('' === $identifierData['url'] || 'https://' === $identifierData['url'] ? null : $identifierData['url']);
                    $identifier->setPasswordLength(strlen($password));
                    $this->entityManager->persist($identifier);
                    $this->entityManager->flush();
                }
            }

            $fileProgressBar->finish();
            $io->newLine();
            $io->success('Database filled');

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            $io->newLine();

            return Command::FAILURE;
        }
    }
}
