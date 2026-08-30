<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI-Agent memory + chat session tables (pgvector). Owned by Laravel migrations
 * so Tech-Hub-Backend and AI-Agent share one Supabase Postgres without dual DDL.
 *
 * Skipped on non-pgsql drivers (local sqlite).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS st_turns (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                role TEXT NOT NULL CHECK (role IN ('user', 'assistant', 'system')),
                content TEXT NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                ttl_at TIMESTAMPTZ
            )
        SQL);
        DB::statement('CREATE INDEX IF NOT EXISTS idx_st_turns_user_session ON st_turns (user_id, session_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_st_turns_ttl ON st_turns (ttl_at) WHERE ttl_at IS NOT NULL');

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS mem_facts (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id TEXT NOT NULL,
                text TEXT NOT NULL,
                embedding vector(1536),
                score REAL NOT NULL CHECK (score >= 0 AND score <= 1),
                tags JSONB DEFAULT '[]'::jsonb,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                last_used_at TIMESTAMPTZ DEFAULT NOW(),
                ttl_at TIMESTAMPTZ,
                pin BOOLEAN DEFAULT FALSE,
                deleted BOOLEAN DEFAULT FALSE
            )
        SQL);
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_facts_user_id ON mem_facts(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_facts_score ON mem_facts(score DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_facts_deleted ON mem_facts(deleted) WHERE deleted = FALSE');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_facts_ttl ON mem_facts(ttl_at) WHERE ttl_at IS NOT NULL');

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS mem_episodes (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                summary TEXT NOT NULL,
                summary_embedding vector(1536),
                topic_tags JSONB DEFAULT '[]'::jsonb,
                start_at TIMESTAMPTZ NOT NULL,
                end_at TIMESTAMPTZ NOT NULL,
                turn_count INTEGER NOT NULL CHECK (turn_count > 0),
                turns JSONB NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        SQL);
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_episodes_user_id ON mem_episodes(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mem_episodes_session_id ON mem_episodes(session_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS mem_procedures (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name TEXT NOT NULL UNIQUE,
                description TEXT NOT NULL,
                context_when TEXT,
                steps JSONB NOT NULL,
                conditions JSONB,
                examples JSONB,
                embedding vector(1536),
                category TEXT,
                active BOOLEAN DEFAULT TRUE,
                version INTEGER DEFAULT 1,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS chat_sessions (
                session_id TEXT PRIMARY KEY,
                customer_id TEXT NOT NULL,
                title TEXT NOT NULL,
                last_message_at INTEGER,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL,
                archived INTEGER NOT NULL DEFAULT 0
            )
        SQL);
        DB::statement('CREATE INDEX IF NOT EXISTS idx_chat_sessions_customer_id ON chat_sessions(customer_id)');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('mem_procedures');
        Schema::dropIfExists('mem_episodes');
        Schema::dropIfExists('mem_facts');
        Schema::dropIfExists('st_turns');
    }
};
