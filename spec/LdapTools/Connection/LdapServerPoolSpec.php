<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use LdapTools\Connection\LdapServerPool;
use LdapTools\DomainConfiguration;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Utilities\LdapUtilities;
use LdapTools\Utilities\TcpSocket;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapServerPoolSpec extends ObjectBehavior
{
    protected $servers = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p' ];

    public function let()
    {
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapServerPool');
    }

    function it_should_have_a_SELECT_ORDER_constant()
    {
        $this->shouldHaveConstant('SELECT_ORDER');
    }

    function it_should_have_a_SELECT_RANDOM_constant()
    {
        $this->shouldHaveConstant('SELECT_RANDOM');
    }

    function it_should_have_order_as_the_default_selection_method()
    {
        $this->getSelectionMethod()->shouldBeEqualTo(LdapServerPool::SELECT_ORDER);
    }

    function it_should_change_the_selection_method_when_calling_setSelectionMethod()
    {
        $this->setSelectionMethod(LdapServerPool::SELECT_RANDOM);
        $this->getSelectionMethod()->shouldBeEqualTo(LdapServerPool::SELECT_RANDOM);
    }

    function it_should_throw_an_error_when_calling_setting_an_invalid_selection_method()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringSetSelectionMethod('foo');
    }

    function it_should_use_the_server_array_as_is_when_using_the_method_order()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers($this->servers);
        $this->beConstructedWith($config);
        $this->getSortedServersArray()->shouldBeEqualTo($this->servers);
    }

    function it_should_randomize_the_server_array_when_using_the_method_random()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers($this->servers);
        $this->beConstructedWith($config);

        $this->setSelectionMethod(LdapServerPool::SELECT_RANDOM);
        $this->getSortedServersArray()->shouldNotBeEqualTo($this->servers);
    }

    function it_should_throw_an_exception_when_no_servers_are_available(TcpSocket $tcp)
    {
        $tcp->connect('foo')->willReturn(false);
        $config = new DomainConfiguration('example.com');
        $config->setServers(['foo']);
        $this->beConstructedWith($config, $tcp);

        $this->shouldThrow(new LdapConnectionException('No LDAP server is available.'))->duringGetServer();
    }

    function it_should_lookup_servers_via_dns_if_no_servers_are_defined(TcpSocket $tcp, LdapUtilities $utils)
    {
        $tcp->connect('foo.example.com')->willReturn(false);
        $tcp->connect('bar.example.com')->willReturn(true);
        $tcp->close()->willReturn(null);
        $utils->getLdapServersForDomain('example.com')->willReturn(['foo.example.com', 'bar.example.com']);
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config, $tcp, $utils);

        $this->getServer()->shouldBeEqualTo('bar.example.com');
    }

    function it_should_throw_an_error_when_no_servers_are_returned_from_dns(TcpSocket $tcp, LdapUtilities $utils)
    {
        $utils->getLdapServersForDomain('example.com')->willReturn([]);
        $config = new DomainConfiguration('example.com');
        $this->beConstructedWith($config, $tcp, $utils);

        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringGetServer();
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Connection\LdapServerPool::'.$constant);
            }
        ];
    }
}
