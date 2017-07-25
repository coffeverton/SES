<?php

namespace SesBundle\Controller;

use SesBundle\Entity\Recipient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Recipient controller.
 *
 * @Route("recipients")
 */
class RecipientController extends Controller
{
    /**
     * Lists all recipient entities.
     *
     * @Route("/", name="recipient_index")
     * @Route("/page/{page}", name="recipient_index_pagination")
     * @Route("/search", name="recipient_index_search")

     * @Method("GET")
     */
	public function indexAction($page = 1, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = 20;
        $search = $request->query->get('search');
        $recipients = $em->getRepository('SesBundle:Recipient')->getRecipients($page, $limit, $search);
        
        // You can also call the count methods (check PHPDoc for `paginate()`)
        # Total fetched (ie: `5` items)
        $totalFetched = $recipients->getIterator()->count();
        
        # Count of ALL items (ie: `20` items)
        
        $total = $recipients->count();
        
        $maxPages = ceil($total	/ $limit);
        $thisPage = $page;

        return $this->render('recipient/index.html.twig', array(
            'recipients' => $recipients,
        	'total' => $total,
        	'maxpages' => $maxPages,
        	'thispage' => $thisPage,
        	'totalfetched' => $totalFetched,
        	'search' => $search,
        ));
    }

    
    /**
     * Finds and displays a recipient entity.
     *
     * @Route("/{id}", name="recipient_show")
     * @Method("GET")
     */
    public function showAction(Recipient $recipient)
    {
        $deleteForm = $this->createDeleteForm($recipient);

        return $this->render('recipient/show.html.twig', array(
            'recipient' => $recipient,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    
    /**
     * Deletes a recipient entity.
     *
     * @Route("/{id}", name="recipient_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Recipient $recipient)
    {
        $form = $this->createDeleteForm($recipient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($recipient);
            $em->flush($recipient);
        }

        return $this->redirectToRoute('recipient_index');
    }

    /**
     * Creates a form to delete a recipient entity.
     *
     * @param Recipient $recipient The recipient entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Recipient $recipient)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('recipient_delete', array('id' => $recipient->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
