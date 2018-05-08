<?php

namespace SesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//use SesBundle\Entity\Recipient;
//use SesBundle\Controller\DefaultController;

class ProcessCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('notifications:process')
            ->setDescription('Process the subscriptions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting...");
        
        $container = $this->getContainer();
        $notifications = $container->get('ses.notifications');
        $array = $notifications->processRequests();

        $output->writeln("{$array['p']} itens processados\r\n{$array['n']} notificacoes\r\n{$array['c']} confirmacoes\r\n{$array['e']} erros");
        
        $output->writeln("Done!\r\n");
    }
}
