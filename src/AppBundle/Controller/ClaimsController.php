<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Claim;
use AppBundle\Entity\Decklist;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * API Controller for Claims (decklist ranking in tournaments)
 *
 * @author cbertolini
 * @Route("/api/2.1/private/decklists/{decklist_id}/claims")
 */
class ClaimsController extends AbstractOauthController
{
    private function deserializeClaim (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        /* @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->get('jms_serializer');

        $data = json_decode($request->getContent(), true);
        
        /* @var $user \AppBundle\Entity\User */
        $user_id = $data['user_id'];
        $user = $em->getRepository('AppBundle:User')->find($user_id);

        /* @var $claim Claim */
        $claim = $serializer->fromArray($data, 'AppBundle\Entity\Claim');
        $claim->setUser($user);

        return $claim;
    }
          
    /**
     * Create a claim
     * @param Request $request
     * @Route("")
     * @Method("POST")
     */
    public function postAction ($decklist_id, Request $request)
    {
        $client = $this->getOauthClient();
        if(!$client) {
            throw $this->createAccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist Decklist */
        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if(!$decklist) {
            throw $this->createNotFoundException();
        }
        /* @var $claim Claim */
        $claim = $this->deserializeClaim($request);
        $claim->setDecklist($decklist);
        $claim->setClient($client);
        $em->persist($claim);
        $em->flush();

        $jsend = $this->getJsendResponse('success', ['claim' => $claim]);
        $url = $this->generateUrl('app_claims_get', ['decklist_id' => $decklist_id, 'id' => $claim->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->createJsonResponse($jsend, 201, ['Location' => $url]);
    }

    /**
     * 
     * @param integer $decklist_id
     * @param integer $id
     * @param Request $request
     * @return Claim
     * @throws \Exception
     */
    protected function retrieveClaim ($decklist_id, $id)
    {
        $client = $this->getOauthClient();
        if(!$client) {
            throw $this->createAccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist Decklist */
        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if(!$decklist) {
            throw $this->createNotFoundException();
        }
        /* @var $claim Claim */
        $claim = $em->getRepository('AppBundle:Claim')->find($id);
        if(!$claim) {
            throw $this->createNotFoundException();
        }
        if($claim->getDecklist()->getId() !== $decklist->getId()) {
            throw $this->createNotFoundException();
        }
        if($claim->getClient()->getId() !== $client->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $claim;
    }

    /**
     * Return a claim
     * @param integer $id
     * @Route("/{id}")
     * @Method("GET")
     */
    public function getAction ($decklist_id, $id)
    {
        $claim = $this->retrieveClaim($decklist_id, $id);
        $jsend = $this->getJsendResponse('success', ['claim' => $claim]);
        return $this->createJsonResponse($jsend);
    }

    /**
     * Update a claim
     * @param integer $id
     * @Route("/{id}")
     * @Method("PUT")
     */
    public function putAction ($decklist_id, $id, Request $request)
    {
        $claim = $this->retrieveClaim($decklist_id, $id);
        /* @var $updatingClaim Claim */
        $updatingClaim = $this->deserializeClaim($request);
        $claim->setName($updatingClaim->getName());
        $claim->setRank($updatingClaim->getRank());
        $claim->setParticipants($updatingClaim->getParticipants());
        $claim->setUrl($updatingClaim->getUrl());
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $jsend = $this->getJsendResponse('success', ['claim' => $claim]);

        return $this->createJsonResponse($jsend);
    }

    /**
     * Delete a claim
     * @param integer $id
     * @Route("/{id}")
     * @Method("DELETE")
     */
    public function deleteAction ($decklist_id, $id)
    {
        $claim = $this->retrieveClaim($decklist_id, $id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($claim);
        $em->flush();

        $jsend = $this->getJsendResponse('success', null);

        return $this->createJsonResponse($jsend);
    }

}
