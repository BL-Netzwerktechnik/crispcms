<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace crisp\core;

class Tracing
{
    public static function getParentSpan()
    {
        return \Sentry\SentrySdk::getCurrentHub()->getSpan();
    }


    public static function traceFunction(\Sentry\Tracing\SpanContext $spanContext, callable $callback, ...$args)
    {
        $span = self::getParentSpan();

        if (null !== $span) {
            $span = $span->startChild($spanContext);
        }

        try {
            return $callback(...$args);
        } finally {
            if (null !== $span) {
                $span->finish();
            }
        }
    }

    /**
     * Start a Sentry span.
     */
    public static function startSentrySpan($method, $description, $parent)
    {

        $context = new \Sentry\Tracing\SpanContext();
        $context->setOp($method);
        $context->setDescription($description);
        $span = $parent->startChild($context);

        \Sentry\SentrySdk::getCurrentHub()->setSpan($span);

        return $span;
    }

    /**
     * Finish a Sentry span.
     */
    public static function finishSentrySpan($span, $parent)
    {
        $span->finish();
        \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
    }
}
