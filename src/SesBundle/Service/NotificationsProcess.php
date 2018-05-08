<?php
namespace SesBundle\Service;

use SesBundle\Entity\Subscription;
use SesBundle\Entity\Recipient;

class NotificationsProcess {
    
    private $root_dir;
    private $em;
    
    public function __construct($root_dir, $em)
    {
        $this->root_dir = $root_dir;
        $this->em = $em;
    }
    
    public function processRequests()
    {
        $tmp_dir = $this->root_dir.'/../var/notifications/tmp/';
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
        return array('p' => $p, 'n' => $n, 'c' => $c, 'e' => $e);
    }
    
    private function confirmSubscription($data)
    {
        $subscription = $this->em->getRepository('SesBundle:Subscription')->find($data->local_id);
        $subscription->setStatus(1);
        $this->em->persist($subscription);
        $this->em->flush($subscription);
        file_get_contents($data->SubscribeURL);//para confirmar a inscricao
    }
    
    private function parseNotification($data)
    {
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
                    $arr_subscriptions[$data->local_id] = $this->em->getRepository('SesBundle:Subscription')->find($data->local_id); 
                }

    //            $recipient->setSubscriptionId($data->local_id);
                $recipient->setSubscriptionId($arr_subscriptions[$data->local_id]);

                $this->em->persist($recipient);
                $this->em->flush($recipient);
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
        $folder = $this->root_dir.'/../var/notifications/processed/'.date('y/m/d');
        @mkdir($folder, 0777, true);
        rename(
            $file->getPathname()
            , $folder.'/'.$file->getBasename()
        );
    }
}
