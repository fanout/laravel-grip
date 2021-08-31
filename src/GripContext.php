<?php

namespace Fanout\LaravelGrip;

use Fanout\Grip\Data\GripInstruct;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\LaravelGrip\Errors\GripInstructAlreadyStartedError;
use Fanout\LaravelGrip\Errors\GripInstructNotAvailableError;

class GripContext {
    public bool $handled;
    public bool $proxied;
    public bool $signed;
    public bool $grip_proxy_required;
    /**
     * @var GripInstruct|null
     */
    private ?GripInstruct $grip_instruct;
    /**
     * @var WebSocketContext|null
     */
    private ?WebSocketContext $ws_context;

    public function __construct() {
        $this->handled = false;
        $this->proxied = false;
        $this->signed = false;
        $this->grip_proxy_required = false;
        $this->grip_instruct = null;
        $this->ws_context = null;
    }

    public function is_handled(): bool {
        return $this->handled;
    }

    public function set_is_handled( $value = true ) {
        return $this->handled = $value;
    }

    public function is_proxied(): bool {
        return $this->proxied;
    }

    public function set_is_proxied( $value = true ) {
        return $this->proxied = $value;
    }

    public function is_signed(): bool {
        return $this->signed;
    }

    public function set_is_signed( $value = true ) {
        return $this->signed = $value;
    }

    public function is_grip_proxy_required(): bool {
        return $this->grip_proxy_required;
    }

    public function set_is_grip_proxy_required( $value = true ) {
        return $this->grip_proxy_required = $value;
    }

    public function has_instruct(): bool {
        return $this->grip_instruct !== null;
    }

    public function get_instruct(): GripInstruct {
        if ($this->grip_instruct !== null) {
            return $this->grip_instruct;
        }
        return $this->start_instruct();
    }

    public function start_instruct(): GripInstruct {
        if (!$this->is_proxied()) {
            throw new GripInstructNotAvailableError();
        }
        if ($this->grip_instruct !== null) {
            throw new GripInstructAlreadyStartedError();
        }
        $this->grip_instruct = new GripInstruct();
        return $this->grip_instruct;
    }

    public function get_ws_context(): ?WebSocketContext {
        return $this->ws_context;
    }

    public function set_ws_context( WebSocketContext $ws_context ) {
        $this->ws_context = $ws_context;
    }
}
