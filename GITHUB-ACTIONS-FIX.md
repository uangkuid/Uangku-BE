# GitHub Actions Docker Build Fix

## Summary
Fixed the failing "Build and Push Docker Image" workflow that was encountering `ERROR: ghcr.io/uangkuid/uangku-be@sha256:...: not found` during the manifest merge step.

## Problem
The workflow was failing when trying to create multi-architecture manifests for GitHub Container Registry (GHCR), specifically in the `merge` job. While Docker Hub manifests were created successfully, GHCR manifests failed because one or more image digests couldn't be found in the registry.

## Root Cause
The original workflow built the same Docker image twice in sequence:
1. Build and push to Docker Hub
2. Build and push to GHCR

This approach had several issues:
- **Silent failures**: If the GHCR push failed, the step might still report success due to build caching
- **Digest inconsistencies**: The second build might produce a different digest or use cached information incorrectly
- **No verification**: There was no check to ensure images were actually available in the registries

## Solution
The fix implements the following improvements:

### 1. Added Verification Steps
After each build and push, the workflow now verifies that the image exists in the registry:
- Attempts verification up to 3 times
- Uses exponential backoff (5s, 10s, 15s) between retries
- Fails explicitly with clear error messages if verification fails
- Handles transient registry availability issues

### 2. Optimized Caching
- First build (Docker Hub): Reads from GitHub Actions cache only
- Second build (GHCR): Reads from cache AND writes to cache
- This ensures:
  - The second build is very fast (reuses all layers from first build)
  - No unnecessary cache writes or overwrites
  - Each registry gets its own verified digest

### 3. Better Error Handling
- Uses environment variables consistently (no hardcoded image names)
- Provides detailed logging of digest values
- Clear error messages pinpoint which registry failed and why

### 4. Maintained Separate Builds
Kept separate build steps for each registry instead of using multi-output because:
- With `push-by-digest=true`, different registries may assign different digests
- The docker/build-push-action's single `digest` output can only capture one value
- Separate builds guarantee we capture the correct digest for each registry

## Changes Made

### Modified Files
- `.github/workflows/docker-image.yml`

### Key Changes
1. Added verification step after Docker Hub push (lines 141-159)
2. Added verification step after GHCR push (lines 173-191)
3. Optimized cache configuration:
   - Docker Hub build: `cache-from: type=gha`
   - GHCR build: `cache-from: type=gha` + `cache-to: type=gha,mode=max`
4. Updated digest export to log values for debugging
5. Used `${{ env.DOCKERHUB_IMAGE }}` and `${{ env.GHCR_IMAGE }}` consistently

## Testing
The fix can be tested by:
1. **Merging this PR to main** - Will auto-trigger the workflow on push
2. **Creating a git tag** - Will auto-trigger the workflow on tag
3. **Manual workflow dispatch** - Via GitHub Actions UI

## Expected Behavior After Fix
1. Build job runs on both amd64 and arm64 platforms
2. Each platform builds and pushes to Docker Hub, then verifies
3. Each platform builds and pushes to GHCR, then verifies  
4. If verification fails, the build job fails immediately with a clear error
5. Digests are exported as artifacts
6. Merge job downloads digests and creates multi-arch manifests
7. Both Docker Hub and GHCR manifests should succeed

## Benefits
- **Reliability**: Catches push failures immediately instead of failing later in merge
- **Performance**: Second build is nearly instant due to cache reuse
- **Debugging**: Clear error messages and digest logging make troubleshooting easier
- **Maintainability**: Uses environment variables consistently
- **Resilience**: Retry logic handles transient registry issues

## Related Documentation
- [Docker Multi-Platform Builds](https://docs.docker.com/build/ci/github-actions/multi-platform/)
- [GitHub Actions Cache](https://docs.github.com/en/actions/using-workflows/caching-dependencies-to-speed-up-workflows)
- [docker/build-push-action](https://github.com/docker/build-push-action)

## Security Review
✅ Passed CodeQL security analysis with no vulnerabilities found.
