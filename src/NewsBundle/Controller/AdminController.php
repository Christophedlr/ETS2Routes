<?php

/**
 * Copyright (C) 2019 Christophe Daloz - De Los Rios
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

namespace NewsBundle\Controller;

use NewsBundle\Entity\News;
use NewsBundle\Form\NewsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Administration of news
 * @package NewsBundle\Controller
 * @author Christophe Daloz - De Los Rios
 * @version 1.0
 */
class AdminController extends Controller
{
    /**
     * @Route("/admin/news/create", name="admin_news_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $news->setAuthor($this->getUser());
            $em->persist($news);
            $em->flush();

            $this->addFlash('success', $this->get('translator')->trans('news.create.success'));

            return $this->redirectToRoute('admin_news_category');
        }

        return $this->render('@News/Admin/create.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
