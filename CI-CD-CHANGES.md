# CI/CD Multi-Architecture Configuration Changes

## Summary
This document describes the changes made to the CI/CD pipeline to support multi-architecture builds (AMD64 + ARM64) for all deployments.

## Changes Made

### 1. GitHub Actions Workflow (`.github/workflows/docker-image.yml`)

#### Trigger Configuration
- **Added**: Tag push trigger to support building on git tags
  ```yaml
  on:
    push:
      branches:
        - main
      tags:
        - '*'
  ```

#### Build Strategy

The workflow builds multi-architecture images (AMD64 + ARM64) for all trigger types:

**For Tag Pushes:**
- Platforms: `linux/amd64,linux/arm64` (multi-architecture)
- Docker tags: 
  - `latest`
  - `{tag-name}` (the actual git tag)
- Example: Creating tag `v1.0.0` will build and push:
  - `oratakashi/uangku-be:latest`
  - `oratakashi/uangku-be:v1.0.0`
  - `ghcr.io/uangkuid/uangku-be:latest`
  - `ghcr.io/uangkuid/uangku-be:v1.0.0`

**For Branch Pushes (main):**
- Platforms: `linux/amd64,linux/arm64` (multi-architecture)
- Docker tags:
  - `dev`
- Example: Pushing to main will build and push:
  - `oratakashi/uangku-be:dev`
  - `ghcr.io/uangkuid/uangku-be:dev`

**For Manual Workflow Dispatch:**
- Platforms: `linux/amd64,linux/arm64` (multi-architecture)
- Docker tags: Based on input parameter (default: `latest`)

### 2. Docker Compose Configuration

#### `docker-compose-dev.yaml`
- **Changed**: Image tag from `latest` to `dev`
- This ensures the development environment uses the latest development build
  ```yaml
  api:
    image: oratakashi/uangku-be:dev
  ```

#### `docker-compose.yaml` (Production)
- **No changes**: Continues to use `latest` tag for production deployments

## Usage

### For Development Workflow
1. Push to `main` branch → Builds multi-architecture image (AMD64 + ARM64) with `dev` tag
2. Use `docker-compose-dev.yaml` to run the latest dev build:
   ```bash
   docker-compose -f docker-compose-dev.yaml up
   ```

### For Production Release
1. Create a git tag (e.g., `v1.0.0`):
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
2. GitHub Actions will build multi-architecture images (AMD64 + ARM64)
3. Images will be tagged as `latest` and `v1.0.0`
4. Use `docker-compose.yaml` to run production:
   ```bash
   docker-compose up
   ```

## Benefits

1. **Multi-Architecture Support**: All builds support both AMD64 and ARM64 for maximum compatibility
2. **Production-Ready Dev Builds**: Development images use the same architecture as production
3. **Clear Separation**: `dev` tag for development, `latest` tag for production
4. **Version Tracking**: Git tags are preserved as Docker image tags for easier version management
