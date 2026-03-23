# SupportShelf AI

## Project Write-Up

SupportShelf AI is a portfolio-grade e-commerce support assistant built to answer customer questions using real support content instead of generic AI guesses.

The product is designed around a familiar retail support experience. A user opens a modern chat interface, asks a product or policy question, and receives a grounded answer based on crawled support articles, imported manuals, and store policy documents.

This project was built to demonstrate full-stack product thinking, retrieval-augmented generation, real-time chat UX, ingestion pipelines, vector search, and production deployment.

Live application:

- `https://supportshelf.mkbuilds.live`

## Problem

Most customer support chatbots either:

- rely on hardcoded FAQs
- provide shallow keyword search
- or generate answers without enough grounding in actual support material

For e-commerce support, this creates a poor customer experience. Shoppers need accurate answers to practical questions such as:

- How do I reset this device?
- Does this product support a specific feature?
- What is the return window?
- What does the warranty cover?
- Which accessories are compatible?

SupportShelf AI solves this by combining document ingestion, semantic retrieval, and OpenAI-generated answers into a single support workflow.

## Solution

SupportShelf AI ingests support knowledge from two main sources:

- crawled support pages and help-center content
- uploaded support documents such as manuals, guides, and policy files

That content is normalized, chunked, embedded with OpenAI, and stored in Weaviate for semantic retrieval. When a user sends a question, the system retrieves the most relevant chunks, builds grounded context, and sends that context into the OpenAI Responses API to generate an answer.

The experience is wrapped in a polished chat UI with realtime updates, conversation history, and a customer-facing interface that feels like a real SaaS support product rather than a technical demo.

## Core Capabilities

- Crawl public support pages and extract readable support content
- Import local support files such as PDFs, text documents, and markdown
- Normalize and chunk support content for retrieval
- Generate embeddings with OpenAI
- Store vectors in Weaviate
- Retrieve semantically relevant support chunks for each question
- Generate grounded answers through the OpenAI Responses API
- Persist conversations and messages
- Stream assistant responses over WebSockets
- Provide an admin ingestion dashboard for source and document management

## Architecture Summary

The application is built with Laravel as the core backend and product layer.

At a high level, the flow looks like this:

1. Support pages are crawled and support files are imported
2. Documents are normalized and split into chunks
3. Chunks are embedded with OpenAI
4. Embeddings are indexed in Weaviate
5. A user question is embedded and used for semantic retrieval
6. Retrieved context is sent to the OpenAI Responses API
7. The answer is streamed back into the chat UI in real time

## Stack

- Laravel 13
- PHP 8.3
- MySQL for relational storage
- Redis for queue and realtime infrastructure support
- Symfony `HttpBrowser` and `DomCrawler` for crawling
- Guzzle for HTTP requests
- OpenAI Responses API for grounded answers
- OpenAI embeddings for semantic search
- Weaviate as the vector database
- Laravel Reverb for WebSockets
- Blade, Tailwind CSS, Alpine-style frontend behavior, and Outfit for the UI
- Docker Compose for production orchestration

## Milestones Achieved

### Milestone 1: Product Foundation

The project foundation was established with a focus on shaping the app as a real product instead of a raw backend demo.

Delivered:

- project structure and environment configuration
- support assistant branding and naming
- base chat shell and navigation
- overview page and ingestion page
- Reverb, OpenAI, and vector-store configuration scaffolding

Outcome:

- the app had a clear product direction and a UI foundation ready for implementation

### Milestone 2: Ingestion Pipeline

The knowledge ingestion layer was built to turn support content into structured application data.

Delivered:

- source registry for support websites
- crawler service for public support pages
- document import flow for local support files
- database models for sources, documents, chunks, and crawl runs
- normalization and chunking pipeline
- admin ingestion dashboard
- Artisan commands and jobs for crawling and importing

Outcome:

- the app could collect, clean, and persist support knowledge in the relational database

### Milestone 3: Embeddings and Vector Search

The semantic retrieval layer was added to make the chatbot context-aware.

Delivered:

- OpenAI embedding service
- Weaviate vector-store integration
- vector sync jobs and commands
- semantic retrieval service
- support search endpoint
- vector indexing status visibility in the dashboard and status API

Outcome:

- ingested content became searchable by meaning, not just by keywords

### Milestone 4: Chat Backend and RAG Flow

Retrieval was connected to a production-style AI answer loop.

Delivered:

- conversation and message persistence
- chat service and prompt-building layer
- Responses API integration
- grounded answer generation using retrieved context
- assistant message events and background generation jobs
- realtime message updates over Reverb

Outcome:

- the project became a working retrieval-augmented support assistant

### Milestone 5: Product UI and UX Refinement

The interface was refined so the application feels customer-facing and portfolio-ready.

Delivered:

- ChatGPT-style chat layout
- recent conversations sidebar
- suggested prompts for empty-state conversations
- mobile offcanvas navigation
- light and dark mode toggle
- improved chat bubble styling
- markdown-style formatting support for AI responses
- cleaner overview and ingestion experiences

Outcome:

- the application now presents well both as a demo product and as a polished engineering portfolio piece

### Milestone 6: Production Deployment

The application was deployed to a VPS using Docker Compose, with HTTPS and websocket routing added for production use.

Delivered:

- production Dockerfile and Compose stack
- web, worker, scheduler, reverb, mysql, redis, and weaviate services
- production environment configuration
- public domain setup for `supportshelf.mkbuilds.live`
- TLS certificate issuance
- reverse proxy configuration for both app traffic and websocket traffic
- proxy-aware Laravel configuration

Outcome:

- the application is deployed and accessible publicly over HTTPS

## Current Status

The project is now a full end-to-end AI support application with:

- a working ingestion pipeline
- vector search with Weaviate
- OpenAI-powered grounded answers
- realtime chat delivery
- a polished frontend
- a live production deployment

At the time of writing, the live application infrastructure is ready and stable. The next operational step is to populate the production instance with real support sources and documents so the public deployment has a full knowledge base to answer from.

## Key Engineering Decisions

### Why Symfony crawler components instead of classic Goutte

Classic `fabpot/goutte` is not a clean fit on the current Laravel and Symfony stack used in this project. Using Symfony `HttpBrowser` and `DomCrawler` gives the same crawling direction while staying compatible with the modern framework versions.

### Why Weaviate

Weaviate was chosen because it is a strong portfolio-grade vector database that is easy to run locally and in containers while still feeling like a serious production tool.

### Why Docker Compose for deployment

The deployment needed multiple cooperating services:

- web
- queue worker
- scheduler
- websocket server
- database
- redis
- vector database

Docker Compose made it practical to run the whole application as a cohesive production stack on a VPS.

## What This Project Demonstrates

This project shows the ability to build and ship:

- a real Laravel product
- a retrieval-augmented AI workflow
- a data ingestion system
- a vector search integration
- a realtime chat interface
- a multi-service production deployment

It also demonstrates practical full-stack decision-making across backend services, frontend UX, realtime infrastructure, and deployment operations.

## Next Milestones

Good next steps for the project include:

- populate production with a richer support knowledge base
- add source citations directly in the chat UI
- schedule recurring recrawls
- add feedback signals on answers
- improve observability for ingestion and answer quality
- replace the default Laravel README with a project-specific README

## Summary

SupportShelf AI started as a simple plan for an OpenAI Responses API project and evolved into a full product:

- a support knowledge ingestion system
- a semantic retrieval engine
- a realtime AI chat experience
- and a live deployed application

It is now a strong portfolio project because it shows both technical depth and product polish in a single system.
