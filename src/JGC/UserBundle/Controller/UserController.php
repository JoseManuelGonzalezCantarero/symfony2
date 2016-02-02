<?php
namespace JGC\UserBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
class UserController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('JGCUserBundle:User')->findAll();

        $res = 'Lista de usuarios: <br />';

        foreach ($users as $user)
        {
            $res .= 'Usuario: '.$user->getUsername().' - Email: '.$user->getEmail().'<br />';
        }

        return new Response($res);
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('JGCUserBundle:User');

        $user = $repository->find($id);

        $res = 'Usuario con ID: '.$id.'<br />';

        $res .= 'Usuario: '.$user->getUsername().' - Email: '.$user->getEmail().'<br />';

        return new Response($res);
    }
}