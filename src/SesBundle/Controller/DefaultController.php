<?php

namespace SesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use SesBundle\Entity\Subscription;
use SesBundle\Entity\Recipient;

class DefaultController extends Controller
{
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
        $tmp_dir = $this->get('kernel')->getRootDir().'/../var/notifications/tmp/';
        $Directory = new \RecursiveDirectoryIterator($tmp_dir);
        $p = 0;
        $n = 0;
        $c = 0;
        $e = 0;
        
        $Iterator = new \RecursiveIteratorIterator($Directory);
        foreach($Iterator as $item)
        {
            if($item->isFile())
            {
                $data = json_decode(file_get_contents($item->getPathname()));
                if($data){
                    switch($data->Type)
                    {
                        case 'Notification':
                            $this->parseNotification($data);
                            $n++;
                            break;
                        case 'SubscriptionConfirmation':
                            $this->confirmSubscription($data);
                            $c++;
                            break;
                        
                        default:
                            var_dump($data->Type);
                            $e++;
                            die;
                    }
                    $p++;
                    $this->discard($item);
                }
            }
        }
        return $this->render(
                'SesBundle:Default:process.html.twig'
                , array('p' => $p, 'n' => $c, 'c' => $c, 'e' => $e)
            );
    }
    
    private function confirmSubscription($data)
    {
        $em = $this->getDoctrine()->getManager();
        $subscription = $em->getRepository('SesBundle:Subscription')->find($data->local_id);
        $subscription->setStatus(1);
        $em->persist($subscription);
        $em->flush($subscription);
        file_get_contents($data->SubscribeURL);//para confirmar a inscricao
    }
    
    private function parseNotification($data)
    {
        $em = $this->getDoctrine()->getManager();
        $message = json_decode($data->Message);
        switch($message->notificationType){
            case 'Delivery':
                $itens = $this->getDeliveryInfo($message);
                break;
            case 'Bounce':
                $itens = $this->getBounceInfo($message);
                break;
            case 'Complaint':
                $itens = $this->getComplaintInfo($message);
                break;
            default:
                print_r($message);
                die;
                break;
        }
        
        if(is_array($itens))
        {
            $arr_subscriptions = array();
            foreach($itens as $item)
            {
                $recipient = new Recipient;
                $recipient->setEmail($item['email']);
                $recipient->setDate($item['date']);
                $recipient->setStatus($item['status']);
                if(isset($item['info']))
                {
                    $recipient->setInfo($item['info']);
                }
                
                if(!array_key_exists($data->local_id, $arr_subscriptions))
                {
                    $arr_subscriptions[$data->local_id] = $em->getRepository('SesBundle:Subscription')->find($data->local_id); 
                }

    //            $recipient->setSubscriptionId($data->local_id);
                $recipient->setSubscriptionId($arr_subscriptions[$data->local_id]);

                $em->persist($recipient);
                $em->flush($recipient);
                unset($recipient);
            }
        }
    }
    
    private function getBounceInfo($message)
    {
        //print_r($message);die;
        if($message->bounce->bounceType !='Permanent')
        {
            return false;
        }
        $timestamp = new \DateTime($message->bounce->timestamp);
        $c = 0;
        $obj = array();
        foreach($message->bounce->bouncedRecipients as $item)
        {
            $obj[$c]['email'] = $item->emailAddress;
            $obj[$c]['date']  = $timestamp;
            $obj[$c]['status']= isset($item->status)?$item->status:0;
            $obj[$c]['info']  = isset($item->diagnosticCode)?$item->diagnosticCode:'';
            $c++;
        }
        
        return $obj;
    }
    
    private function getDeliveryInfo($message)
    {
        $timestamp = new \DateTime($message->delivery->timestamp);
        $status    = substr($message->delivery->smtpResponse, 0, 3);
        $c = 0;
        $obj = array();
        foreach($message->delivery->recipients as $item)
        {
            $obj[$c]['email'] = $item;
            $obj[$c]['date']  = $timestamp;
            $obj[$c]['status']= $status;
            $c++;
        }
        
        return $obj;
    }
    
    private function getComplaintInfo($message)
    {
        $timestamp = new \DateTime($message->complaint->timestamp);
        $c = 0;
        $obj = array();
        foreach($message->complaint->complainedRecipients as $item)
        {
            $obj[$c]['email'] = $item->emailAddress;
            $obj[$c]['date']  = $timestamp;
            $obj[$c]['status']= '-1';
            $obj[$c]['info']  = 'Complaint';
            $c++;
        }
        
        return $obj;
    }
    
    private function discard($file)
    {
        $folder = $this->get('kernel')->getRootDir().'/../var/notifications/processed/'.date('y/m/d');
        @mkdir($folder, 0777, true);
        rename(
            $file->getPathname()
            , $folder.'/'.$file->getBasename()
        );
    }
}
