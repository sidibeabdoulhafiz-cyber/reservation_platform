<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Envoi des e-mails via l'API transactionnelle Brevo (ex-Sendinblue).
 * https://developers.brevo.com/reference/sendtransacemail
 *
 * Sans dépendance externe : utilise le client HTTP de Laravel.
 */
class BrevoTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $endpoint = 'https://api.brevo.com/v3/smtp/email',
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('Clé API Brevo manquante : renseignez BREVO_API_KEY dans le fichier .env.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'accept' => 'application/json',
        ])
            ->timeout(15)
            ->retry(2, 500, throw: false)
            ->post($this->endpoint, $this->payload($email, $envelope));

        if (! $response->successful()) {
            throw new TransportException(sprintf(
                'Brevo a refusé l’e-mail (HTTP %d) : %s',
                $response->status(),
                $response->json('message') ?? $response->body(),
            ));
        }

        if ($id = $response->json('messageId')) {
            $message->setMessageId($id);
        }
    }

    /** @return array<string, mixed> */
    private function payload(Email $email, Envelope $envelope): array
    {
        $sender = $envelope->getSender();
        $cc = array_map(fn (Address $a) => $a->getAddress(), $email->getCc());
        $bcc = array_map(fn (Address $a) => $a->getAddress(), $email->getBcc());

        $to = array_values(array_filter(
            $envelope->getRecipients(),
            fn (Address $a) => ! in_array($a->getAddress(), [...$cc, ...$bcc], true),
        ));

        $payload = [
            'sender' => $this->adresse($email->getFrom()[0] ?? $sender),
            'to' => array_map(fn (Address $a) => $this->adresse($a), $to),
            'subject' => (string) $email->getSubject(),
        ];

        if ($email->getCc()) {
            $payload['cc'] = array_map(fn (Address $a) => $this->adresse($a), $email->getCc());
        }
        if ($email->getBcc()) {
            $payload['bcc'] = array_map(fn (Address $a) => $this->adresse($a), $email->getBcc());
        }
        if ($reply = $email->getReplyTo()[0] ?? null) {
            $payload['replyTo'] = $this->adresse($reply);
        }
        if ($html = $email->getHtmlBody()) {
            $payload['htmlContent'] = is_resource($html) ? stream_get_contents($html) : (string) $html;
        }
        if ($text = $email->getTextBody()) {
            $payload['textContent'] = is_resource($text) ? stream_get_contents($text) : (string) $text;
        }

        $pieces = [];
        foreach ($email->getAttachments() as $part) {
            $pieces[] = [
                'name' => $part->getFilename() ?? 'piece-jointe',
                'content' => base64_encode($part->getBody()),
            ];
        }
        if ($pieces) {
            $payload['attachment'] = $pieces;
        }

        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $nom = $header->getName();
            if (str_starts_with(strtolower($nom), 'x-')) {
                $headers[$nom] = $header->getBodyAsString();
            }
        }
        if ($headers) {
            $payload['headers'] = $headers;
        }

        return $payload;
    }

    /** @return array{email: string, name?: string} */
    private function adresse(Address $a): array
    {
        return array_filter([
            'email' => $a->getAddress(),
            'name' => $a->getName() ?: null,
        ]);
    }

    public function __toString(): string
    {
        return 'brevo+api://api.brevo.com';
    }
}
