<?php

namespace Pushword\TemplateEditor;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @IsGranted("ROLE_PUSHWORD_ADMIN_THEME")
 */
class ElementAdmin extends AbstractController
{
    protected KernelInterface $kernel;

    /** @required */
    public function setKernel(KernelInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    protected function getElements(): ElementRepository
    {
        return new ElementRepository($this->kernel->getProjectDir().'/templates');
    }

    public function listElement(): Response
    {
        return $this->render('@pwTemplateEditor/list.html.twig', ['elements' => $this->getElements()->getAll()]);
    }

    protected function getElement(?string $encodedPath): Element
    {
        if (null !== $encodedPath) {
            $element = $this->getElements()->getOneByEncodedPath($encodedPath);
            if (! $element) {
                throw $this->createNotFoundException('`'.$encodedPath.'` element does not exist...');
            }
        }

        return $element ?? new Element($this->kernel->getProjectDir().'/templates');
    }

    protected function clearTwigCache(): void
    {
        $twigCacheFolder = $this->get('twig')->getCache(true);

        $process = new Process(['rm', '-rf', $twigCacheFolder]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function editElement(?string $encodedPath = null, Request $request = null): Response
    {
        $element = $this->getElement($encodedPath);

        $form = $this->editElementForm($element);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $element = $form->getData();
            $element->storeElement();

            $this->clearTwigCache();

            return $this->redirectToRoute(
                'pushword_template_editor_edit',
                [
                    'encodedPath' => $element->getEncodedPath(),
                ]
            );
        }

        return $this->render(
            '@pwTemplateEditor/edit.html.twig',
            [
                'element' => $element,
                'form' => $form->createView(),
            ]
        );
    }

    protected function editElementForm(Element $element): FormInterface
    {
        return $this->createFormBuilder($element)
            ->add('path', TextType::class)
            ->add('code', TextareaType::class, [
                'attr' => [
                    'style' => 'min-height: 90vh;font-size:125%;',
                    'data-editor' => 'twig',
                    'data-gutter' => 0,
                ],
                'required' => false,
            ])

            ->getForm();
    }

    public function deleteElement(string $encodedPath, Request $request): Response
    {
        $element = $this->getElement($encodedPath);

        $form = $this->createFormBuilder()
            ->add('delete', SubmitType::class, ['label' => 'Supprimer', 'attr' => ['class' => 'btn-danger']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $element->deleteElement();

            $this->addFlash('error', 'Element supprimé.');

            return $this->redirectToRoute('pushword_template_editor_list');
        }

        return $this->render(
            '@pwTemplateEditor/delete.html.twig',
            [
                'form' => $form->createView(),
                'element' => $element,
            ]
        );
    }
}
