<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendMessageToKafkaCommand extends Command
{
    protected static $defaultName = 'app:send-message-to-kafka';
    protected static $defaultDescription = 'Отправка сообщения в кафку';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::REQUIRED, 'Количество сообщений')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfMessages = $input->getArgument('arg1');

        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', 'kafka:9092');

        //If you need to produce exactly once and want to keep the original produce order, uncomment the line below
        //$conf->set('enable.idempotence', 'true');

        $producer = new \RdKafka\Producer($conf);

        $topic = $producer->newTopic("test");

        for ($i = 0; $i < $numberOfMessages; $i++) {
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, "Message $i");
            $producer->poll(0);
        }

        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }

        return Command::SUCCESS;
    }
}
