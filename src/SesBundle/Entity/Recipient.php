<?php

namespace SesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Recipient
 *
 * @ORM\Table(name="recipient")
 * @ORM\Entity(repositoryClass="SesBundle\Repository\RecipientRepository")
 */
class Recipient
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetimetz")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=5)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="blob", nullable=true)
     */
    private $info;


    /**
     * @var int
     * @ORM\ManyToOne(targetEntity="Subscription", inversedBy="recipients")
     * @ORM\JoinColumn(name="subscriptionId", referencedColumnName="id")
     */
    private $subscriptionId;
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Recipient
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Recipient
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Recipient
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set info
     *
     * @param string $info
     *
     * @return Recipient
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo()
    {
    	if ($this->info != ''){
    		return stream_get_contents($this->info);
    	}
    	
    	return $this->info;
    }
    
    /**
     * Set subscriptionId
     *
     * @param int $subscriptionId
     *
     * @return Recipient
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;

        return $this;
    }

    
    /**
     * Get subscriptionId
     *
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }
    
    /**
     * Get subscription
     *
     * @return \SesBundle\Entity\Subscription
     */
    public function getSubscription()
    {
    	return $this->subscriptionId;
    }
}

