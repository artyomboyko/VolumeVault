<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an admin-controlled outbound request (destination test/listing,
 * restore download, notification) targets a host that resolves to a private,
 * loopback or link-local address — i.e. a likely SSRF.
 */
class OutboundHostBlockedException extends RuntimeException {}
