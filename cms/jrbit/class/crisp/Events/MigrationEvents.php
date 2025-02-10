<?php

namespace crisp\Events;

/**
 * Generic Theme Events.
 */
final class MigrationEvents
{
    public const BEFORE_MIGRATE = 'migration.before_migrate';
    public const AFTER_MIGRATE = 'migration.after_migrate';
    public const BEFORE_ROLLBACK = 'migration.before_rollback';
    public const AFTER_ROLLBACK = 'migration.after_rollback';
    public const CORE_MIGRATIONS_FINISHED = 'migration.core_migrations_finished';
    public const THEME_MIGRATIONS_FINISHED = 'migration.theme_migrations_finished';

    public const BEFORE_COMMIT = 'migration.before_commit';
    public const AFTER_COMMIT = 'migration.after_commit';

}
