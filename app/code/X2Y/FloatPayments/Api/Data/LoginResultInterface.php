<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Api\Data;

/**
 * Login request data representation
 * @api
 */
interface LoginResultInterface
{
    const MERCHANT_ID  = 'id';
    const NAME         = 'name';
    const TERM_RULES   = 'termRules';
    const ACCESS_TOKEN = 'token';

    /**
     * Set id
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Set term rules
     *
     * @param array $termRules
     * @return $this
     */
    public function setTermRules(array $termRules): self;

    /**
     * Set token
     *
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self;

    /**
     * Get id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get term rules
     *
     * @return array
     */
    public function getTermRules(): array;

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(): string;
}
