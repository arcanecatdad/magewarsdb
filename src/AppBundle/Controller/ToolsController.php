<?php

namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ToolsController extends Controller
{
    public function demoAction()
    {
        return $this->render('AppBundle:Tools:demo.html.twig');
    }
    
    
    public function extdecklistAction($id)
    {
    	return $this->render('AppBundle:Tools:demo-ext-decklist.html.twig', [
    			'id' => $id
    	]);
    }
}