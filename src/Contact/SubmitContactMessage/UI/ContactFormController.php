<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\UI;

use App\Contact\SubmitContactMessage\Application\ContactMessageNotificationFailed;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactFormController extends AbstractController
{
    #[Route('/{_locale<%app.supported_locales%>}/contact-form', name: 'app_contact_form', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SubmitContactMessageHandler $handler,
        #[Autowire(service: 'monolog.logger.mailer')]
        LoggerInterface $logger,
    ): Response {
        $contactMessage = new ContactMessageData();

        $form = $this->createForm(ContactMessageType::class, $contactMessage, [
            'action' => $this->generateUrl('app_contact_form'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new SubmitContactMessageCommand(
                fullName: $contactMessage->fullName,
                email: $contactMessage->email,
                subject: $contactMessage->subject,
                message: $contactMessage->message,
            );

            try {
                $handler($command);
            } catch (ContactMessageNotificationFailed $exception) {
                $logger->error(sprintf(
                    'Could not send email about new submission, id: %s. Error: %s',
                    $exception->result->contactMessageId,
                    $exception->getPrevious()?->getMessage() ?? $exception->getMessage(),
                ), [
                    'exception' => $exception,
                    'contactMessageId' => $exception->result->contactMessageId,
                ]);
            }

            $this->addFlash('success', 'contact_message.submit_success');

            return $this->render('contact/submit_contact_message/_success.html.twig');
        }

        return $this->render('contact/submit_contact_message/_form.html.twig', [
            'form' => $form,
        ]);
    }
}
