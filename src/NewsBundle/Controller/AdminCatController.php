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

use NewsBundle\Entity\Category;
use NewsBundle\Form\CategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Administration of news
 * @package NewsBundle\Controller
 * @author Christophe Daloz - De Los Rios
 * @version 1.0
 */
class AdminCatController extends Controller
{
    /**
     * @Route("/admin/news/category", name="admin_news_category")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function displayAction()
    {
        $repos = $this->getDoctrine()->getRepository(Category::class);
        $categories = $repos->findAll();

        return $this->render('@News/Admin/Category/display.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * Create the new category
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/news/category/create", name="admin_news_category_create")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $repos = $this->getDoctrine()->getRepository(Category::class);

            if (is_object($repos->findOneBy(['name' => $category->getName()]))) {
                $this->addFlash('danger', $this->get('translator')->trans('category.create.error'));
            } {
                $em->persist($category);
                $em->flush();

                $this->addFlash('success', $this->get('translator')->trans('category.create.success'));

                return $this->redirectToRoute('admin_news_category');
            }
        }

        return $this->render('@News/Admin/Category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Change the category
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/news/category/change/{id}", name="admin_news_category_change")
     *
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function changeAction(Request $request, $id)
    {
        $repos = $this->getDoctrine()->getRepository(Category::class);
        $category = $repos->find($id);

        if (is_null($category)) {
            $this->addFlash(
                'danger',
                $this->get('translator')->trans('category.change.not_found', ['%id%' => $id])
            );
            return $this->redirectToRoute('admin_news_category');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->remove('submit');
        $form->add('submit', SubmitType::class, [
            'label' => 'category.change.submit',
            'attr' => ['class' => 'btn-primary'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash(
                'success',
                $this->get('translator')->trans('category.change.success', ['%id%' => $id])
            );

            return $this->redirectToRoute('admin_news_category');
        }

        return $this->render('@News/Admin/Category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete a category
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/news/category/delete/{id}", name="admin_news_category_delete")
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $repos = $this->getDoctrine()->getRepository(Category::class);
        $category = $repos->find($id);

        if (is_null($category)) {
            $this->addFlash(
                'danger',
                $this->get('translator')->trans('category.change.not_found', ['%id%' => $id])
            );
            return $this->redirectToRoute('admin_news_category');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        $this->addFlash(
            'success',
            $this->get('translator')->trans('category.delete.success', ['%id%' => $id])
        );

        return $this->redirectToRoute('admin_news_category');
    }
}
