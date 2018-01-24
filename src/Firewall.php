<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use M6Web\Component\Firewall\Firewall as IpFirewall;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Firewall implements MiddlewareInterface
{
    /**
     * @var array|null
     */
    private $whitelist;

    /**
     * @var array|null
     */
    private $blacklist;

    /**
     * @var string|null
     */
    private $ipAttribute;

    /**
     * Constructor. Set the whitelist.
     */
    public function __construct(array $whitelist = null)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Set ips not allowed.
     */
    public function blacklist(array $blacklist): self
    {
        $this->blacklist = $blacklist;

        return $this;
    }

    /**
     * Set the attribute name to get the client ip.
     */
    public function ipAttribute(string $ipAttribute): self
    {
        $this->ipAttribute = $ipAttribute;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $this->getIp($request);

        if (empty($ip)) {
            return Utils\Factory::createResponse(403);
        }

        $firewall = new IpFirewall();

        if (!empty($this->whitelist)) {
            $firewall->addList($this->whitelist, 'whitelist', true);
        }

        if (!empty($this->blacklist)) {
            $firewall->addList($this->blacklist, 'blacklist', false);
        }

        $firewall->setIpAddress($ip);

        if (!$firewall->handle()) {
            return Utils\Factory::createResponse(403);
        }

        return $handler->handle($request);
    }

    /**
     * Get the client ip.
     */
    private function getIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        if ($this->ipAttribute !== null) {
            return $request->getAttribute($this->ipAttribute);
        }

        return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '';
    }
}
