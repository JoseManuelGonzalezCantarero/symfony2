<?php
namespace JGC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JGC\UserBundle\Entity\User;
use JGC\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('JGCUserBundle:User')->findAll();

        /*
        $res = 'Lista de usuarios: <br />';
        
        foreach ($users as $user)
        {
            $res .= 'Usuario: '.$user->getUsername().' - Email: '.$user->getEmail().'<br />';
        }
        
        return new Response($res);
        */
        return $this->render('JGCUserBundle:User:index.html.twig', array('users' => $users));
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('JGCUserBundle:User');

        $user = $repository->find($id);

        $res = 'Usuario con ID: ' . $id . '<br />';

        $res .= 'Usuario: ' . $user->getUsername() . ' - Email: ' . $user->getEmail() . '<br />';

        return new Response($res);
    }

    public function addAction()
    {
        $user = new User();
        $form = $this->createCreateForm($user);

        return $this->render('JGCUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }

    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('jgc_user_create'), 'method' => 'POST'));
        return $form;
    }

    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if ($form->isValid())
        {
            $password = $form->get('password')->getData();

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $password);

            $user->setPassword($encoded);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('jgc_user_index');
        }

        return $this->render('JGCUserBundle:User:add.html.twig', array('form' => $form->createView()));

    }
}