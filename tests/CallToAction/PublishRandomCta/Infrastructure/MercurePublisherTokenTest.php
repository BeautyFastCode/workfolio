<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\Infrastructure;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\ProtocolVersion;

/**
 * @internal
 *
 * @coversNothing
 */
final class MercurePublisherTokenTest extends KernelTestCase
{
    public function test_configured_publisher_uses_expiring_access_tokens_with_only_cta_grants(): void
    {
        self::bootKernel();
        $hub = self::getContainer()->get(HubInterface::class);
        self::assertInstanceOf(HubInterface::class, $hub);
        self::assertSame(ProtocolVersion::V1, $hub->getProtocolVersion());

        $provider = self::getContainer()->get('mercure.hub.default.jwt.provider');
        self::assertInstanceOf(TokenProviderInterface::class, $provider);
        $jwt = $provider->getJwt();
        self::assertNotEmpty($jwt);
        $token = new Parser(new JoseEncoder())->parse($jwt);
        self::assertInstanceOf(UnencryptedToken::class, $token);
        self::assertSame('at+jwt', $token->headers()->get('typ'));
        self::assertSame('HS256', $token->headers()->get('alg'));
        self::assertSame([$hub->getPublicUrl()], $token->claims()->get('aud'));
        self::assertSame($_SERVER['MERCURE_ISSUER'] ?? $_ENV['MERCURE_ISSUER'], $token->claims()->get('iss'));
        self::assertSame('workfolio-scheduler', $token->claims()->get('sub'));
        self::assertSame('workfolio', $token->claims()->get('client_id'));
        self::assertNotEmpty($token->claims()->get('jti'));
        self::assertFalse($token->claims()->has('mercure'));
        self::assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['publish'],
                'topics' => [
                    ['match' => 'cta_message_topic_en'],
                    ['match' => 'cta_message_topic_pl'],
                ],
            ],
        ], $token->claims()->get('authorization_details'));

        $issuedAt = $token->claims()->get('iat');
        $expiresAt = $token->claims()->get('exp');
        self::assertInstanceOf(\DateTimeImmutable::class, $issuedAt);
        self::assertInstanceOf(\DateTimeImmutable::class, $expiresAt);
        self::assertGreaterThan(time(), $expiresAt->getTimestamp());
        self::assertSame(3600, $expiresAt->getTimestamp() - $issuedAt->getTimestamp());
    }
}
