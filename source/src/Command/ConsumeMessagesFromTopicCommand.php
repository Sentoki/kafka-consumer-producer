<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsumeMessagesFromTopicCommand extends Command
{
    protected static $defaultName = 'app:consume-messages-from-topic';
    protected static $defaultDescription = 'Забрать сообщения из топика';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::REQUIRED, 'Название топика')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $topicName = $input->getArgument('arg1');

        $conf = new \RdKafka\Conf();

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: ";
                    var_dump($partitions);
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: ";
                    var_dump($partitions);
                    $kafka->assign(NULL);
                    break;

                default:
                    throw new \Exception($err);
            }
        });

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', 'myConsumerGroup');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', 'kafka');

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'earliest': start from the beginning
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $consumer->subscribe([$topicName]);

        echo "Waiting for partition assignment... (make take some time when\n";
        echo "quickly re-joining the group after leaving it.)\n";

        while (true) {
            $message = $consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    var_dump($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }

        return Command::SUCCESS;
    }
}
