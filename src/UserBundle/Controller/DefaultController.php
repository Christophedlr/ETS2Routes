<?php

/**
 * Copyright (C) 2018 Christophe Daloz - De Los Rios
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the “Software”), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * The Software is provided “as is”, without warranty of any kind, express or implied, including but not
 * limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
 * In no event shall the authors or copyright holders be liable for any claim, damages or other liability,
 * whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software
 * or the use or other dealings in the Software.
 */

namespace UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use UserBundle\Entity\User;
use UserBundle\Forms\ProfileMailType;
use UserBundle\Forms\ProfilePasswordType;
use UserBundle\Forms\RegisterType;
use UserBundle\Forms\ResetType;

class DefaultController extends Controller
{
    /**
     * @Route("/login", name="user_login")
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $requestStak = new RequestStack();
        $requestStak->push($request);

        $authenticationUtils = new AuthenticationUtils($requestStak);
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $this->addFlash('danger', $this->get('translator')->trans('login.invalid_credentials'));
        }

        return $this->render('user/login.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="user_logout")
     * @throws \Exception
     */
    public function logout()
    {
        throw new \Exception("Don't forget to activate logout in security.yaml");
    }

    /**
     * @Route("/register", name="user_register")
     * @Security("not has_role('ROLE_USER')")
     * @param Request $request
     * @return Response
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword()));
            $user->eraseCredentials();

            $existUser = $this->getDoctrine()->getRepository(User::class)->findBy(['username' => $user->getUsername()]);

            if (!$existUser) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->get('translator')->trans('register.success'));

                return $this->redirectToRoute('homepage');
            }

            $this->addFlash('danger', $this->get('translator')->trans('register.error'));
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/profile", name="user_profile")
     * @Security("has_role('ROLE_USER')")
     * @param Request $request
     * @return Response
     */
    public function profileAction(Request $request)
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ProfilePasswordType::class);
        $passwordForm->handleRequest($request);

        $mailForm = $this->createForm(ProfileMailType::class);
        $mailForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            if ($this->get('security.password_encoder')
                ->isPasswordValid($user, $passwordForm->getData()['oldPassword'])) {
                $user->setPassword(
                    $this->get('security.password_encoder')->encodePassword($user, $passwordForm->getData()['password'])
                );

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->get('translator')->trans('profile.password.success'));

                return $this->redirectToRoute('user_logout');
            } else {
                $this->addFlash('danger', $this->get('translator')->trans('profile.password.error'));
            }
        } elseif ($mailForm->isSubmitted() && $mailForm->isValid()) {
            if ($user->getMail() === $mailForm->getData()['oldMail']) {
                $user->setMail($mailForm->getData()['mail']);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->get('translator')->trans('profile.mail.success'));

                return $this->redirectToRoute('user_logout');
            } else {
                $this->addFlash('danger', $this->get('translator')->trans('profile.mail.error'));
            }
        }

        return $this->render('user/profile.html.twig', [
            'form_password' => $passwordForm->createView(),
            'form_mail' => $mailForm->createView(),
        ]);
    }

    /**
     * @Route("/reset", name="user_reset")
     * @Security("not has_role('ROLE_USER')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function resetAction(Request $request)
    {
        $mail = $request->request->get('mail');
        $mailer = $this->get('mailer');

        if ($mail) {
            $user = $this->getDoctrine()->getRepository(User::class)->findBy(['mail' => $mail])[0];

            if (!$user) {
                $this->addFlash('danger', $this->get('translator')->trans('reset.user.notfound', [], 'forms'));
            } else {
                $code = $this->generatePassword();
                $message = new \Swift_Message($this->get('translator')->trans('reset.object.reinit', [], 'forms'));
                $message
                    ->setFrom('contact@ets2routes.com')
                    ->setTo($user->getMail())
                    ->setBody(
                        $this->renderView('user/mails/password_validation.html.twig', [
                            'user' => $user,
                            'code' => $code
                        ])
                        ,'text/html')
                ;

                if ($mailer->send($message) !== 0) {
                    $user->setValidationCode($code);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', $this->get('translator')->trans('reset.send.success', [], 'forms'));
                    return $this->redirectToRoute('homepage');
                } else {
                    $this->addFlash('danger', $this->get('translator')->trans('reset.send.failure', [], 'forms'));
                }
            }
        }

        return $this->render('user/reset.html.twig', [
            'mail' => $mail,
        ]);
    }

    /**
     * @Route("/reset/password", name="user_reset_password")
     * @Security("not has_role('ROLE_USER')")
     */
    public function resetPasswordAction(Request $request)
    {
        $password = $request->request->get('password');
        $mailer = $this->get('mailer');

        if ($password) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->getDoctrine()->getRepository(User::class)->findBy(['validationCode' => $password['code'], 'mail' => $password['mail']])[0];

            if ($user) {
                $passwd = $this->generatePassword();
                $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $passwd));
                $user->setValidationCode(null);

                $message = new \Swift_Message($this->get('translator')->trans('reset.object.new', [], 'forms'));
                $message
                    ->setFrom('contact@ets2routes.com')
                    ->setTo($user->getMail())
                    ->setBody(
                        $this->renderView('user/mails/new_password.html.twig', [
                            'user' => $user,
                            'password' => $passwd
                        ])
                        ,'text/html')
                ;
                if ($mailer->send($message) !== 0) {
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', $this->get('translator')->trans('reset.password.success', [], 'forms'));
                    return $this->redirectToRoute('homepage');
                } else {
                    $this->addFlash('danger', $this->get('translator')->trans('reset.password.critical', [], 'forms'));
                }
            } else {
                $this->addFlash('danger', $this->get('translator')->trans('reset.password.failure', [], 'forms'));
            }
        }

        return $this->render('user/reset_password.html.twig', [
        ]);
    }

    public function generatePassword()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
