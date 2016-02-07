<?php
namespace JGC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use JGC\UserBundle\Entity\User;
use JGC\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //$users = $em->getRepository('JGCUserBundle:User')->findAll();

        /*
        $res = 'Lista de usuarios: <br />';
        
        foreach ($users as $user)
        {
            $res .= 'Usuario: '.$user->getUsername().' - Email: '.$user->getEmail().'<br />';
        }
        
        return new Response($res);
        */

        $dql = "SELECT u FROM JGCUserBundle:User u ORDER BY u.id DESC";
        $users = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1),
            5
        );

        return $this->render('JGCUserBundle:User:index.html.twig', array('pagination' => $pagination));
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('JGCUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);

        return $this->render('JGCUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }

    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('JGCUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found');
            throw $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $password = $form->get('password')->getData();
            if(!empty(($password)))
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }else
            {
                $recoverPass = $this->recoverPass($id);
                $user->setPassword($recoverPass[0]['password']);
            }

            if($form->get('role')->getData() == 'ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }
            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been modified');
            $this->addFlash('mensaje', $successMessage);
            return $this->redirectToRoute('jgc_user_edit', array('id'=> $user->getId()));
        }
        return $this->render('JGCUserBundle:User:edit.html.twig', array('user'=>$user, 'form'=>$form->createView()));
    }

    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT u.password FROM JGCUserBundle:User u WHERE u.id = :id')->setParameter('id', $id);

        $currentPass = $query->getResult();

        return $currentPass;
    }

    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array('action'=>$this->generateUrl('jgc_user_update', array('id' => $entity->getId())), 'method'=>'PUT'));

        return $form;
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

            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);

            if(count($errorList) == 0)
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);

                $user->setPassword($encoded);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $successMessage = $this->get('translator')->trans('The user has been created');
                $this->addFlash('mensaje', $successMessage);

                return $this->redirectToRoute('jgc_user_index');
            }else
            {
                $errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($errorMessage);
            }
        }

        return $this->render('JGCUserBundle:User:add.html.twig', array('form' => $form->createView()));

    }
}