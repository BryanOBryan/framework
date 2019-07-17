<?php

namespace Kaliba\Database\Contracts;

interface SQLExpression
{

    /**
     * ARITHMETIC OPERATOR 'EQUALS'
     */
    const FIND_EXACT        = 	0x1;

    /**
     * SEARCH OPERATOR  'LIKE'
     */
    const FIND_LIKE         = 	0x2;

    /**
     * ARITHMETIC OPERATOR 'NOT EQUALS'
     */
    const FIND_NOT          = 	0x4;

    /**
     * BOOLEAN OPERATOR 'IS NULL'
     */
    const FIND_NULL         = 	0x8;

    /**
     * ARITHMETIC OPERATOR 'GREATER THAN'
     */
    const FIND_GREATER      = 	0x10;

    /**
     * ARITHMETIC OPERATOR 'LESS THAN'
     */
    const FIND_LESS         =	0x20;

    /**
     * ARITHMETIC OPERATOR 'GREATER THAN OR EQUALTO'
     */
    const FIND_GEQ          = 	0x40;

    /**
     * ARITHMENTIC OPERATOR 'LESS THAN OR EQUALTO'
     */
    const FIND_LEQ          =	0x80;

    /**
     *  LOGICAL OPERATOR 'AND'
     */
    const LOGIC_AND         = 	0x100;

    /**
     * LOGICAL OPERATOR 'OR'
     */
    const LOGIC_OR          = 	0x200;

    /**
     * LOGICAL OPERATOR 'NOT'
     */
    const LOGIC_NOT         = 	0x400;

    /**
     * UNARY OPERATOR 'IN'
     */
    const UNARY_IN          = 	0x800;

    /**
     * UNARY OPERATOR 'EXISTS'
     */
    const UNARY_EXISTS      = 	0x1000;

    /**
     * INNER JOIN
     */
    const JOIN_INNER        =   0x1200;

    /**
     * CROSS JOIN
     */
    const JOIN_CROSS        =   0x1400;

    /**
     * FULL JOIN
     */
    const JOIN_FULL         =   0x1800;

    /**
     * NATURAL JOIN
     */
    const JOIN_NATURAL      =   0x2000;

    /**
     * LEFT JOIN
     */
    const JOIN_LEFT         =   0x2200;

    /**
     * RIGHT JOIN
     */
    const JOIN_RIGHT        =   0x2400;

    /**
     * Returns all values bound to this expression object at this nesting level.
     * Subexpression bound values will not be returned with this function.
     *
     * @return array
     */
    public function getBindings();

    /**
     * Converts the expression to its string representation
     * @return string
     */
    public function getSql();

}