<?php

namespace SesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use SesBundle\Entity\Subscription;
use SesBundle\Entity\Recipient;

class DefaultController extends Controller
{
    
    /**
     * @Route("/")
     */
    public function index()
    {
        return $this->redirectToRoute('subscription_index');
    }
    
    /**
     * recebe uma requisicao e salva na pasta temporaria, para processar depois
     * @Route("/request/{id}", name="request_url")
     */
    public function indexAction(Subscription $s)
    {
        $status = 0;

        // Fetch the raw POST body containing the message
        $postBody = file_get_contents('php://input');
        // JSON decode the body to an array of message data
        $json = json_decode($postBody, true);
        $folder = $this->get('kernel')->getRootDir().'/../var/notifications/tmp/';
        @mkdir($folder, 0777);
        $arquivo = $folder.'/'.uniqid().'.log';
        if ($json) {
            $json['local_id'] = $s->getId();//salva no arquivo json o id da inscricao
            $status = file_put_contents($arquivo, json_encode($json));
            chmod($arquivo, 0777);
        }
        
        return $this->render('SesBundle:Default:index.html.twig', array('status' => $status));
    }
    
    /**
     * @Route("/process")
     */
    public function processRequests()
    {
        $notifications = $this->container->get('ses.notifications');
        $array = $notifications->processRequests();
        
        return $this->render(
            'SesBundle:Default:process.html.twig'
            , $array
        );
    }
    
}
