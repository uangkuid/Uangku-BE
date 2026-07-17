#!/bin/bash

# Test Runner Script for Uangku-BE
# This script helps run tests with proper environment setup

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================${NC}"
echo -e "${BLUE}  Uangku-BE Test Runner${NC}"
echo -e "${BLUE}==================================${NC}"
echo ""

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Vendor directory not found. Running composer install...${NC}"
    composer install --no-interaction --prefer-dist
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}.env file not found. Copying from .env.example...${NC}"
    cp .env.example .env
    php artisan key:generate
fi

# Set testing environment variables if not already set.
# Must be >= 32 chars: AppServiceProvider::assertMinimumPepperLength() rejects
# a shorter-but-set value at boot. MAIN_SECRET_KEY was removed with the old
# IS_NEED_ENCRYPT response-encryption scheme and no longer exists.
if [ -z "$MAIN_SALT_KEY" ]; then
    export MAIN_SALT_KEY="test_salt_key_67890_extended_32ch"
fi

echo -e "${GREEN}Environment ready!${NC}"
echo ""

# Parse command line arguments
TEST_SUITE=""
TEST_FILTER=""
COVERAGE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        --filter)
            TEST_FILTER="$2"
            shift 2
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --help)
            echo "Usage: ./run-tests.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --suite SUITE     Run specific test suite (Unit or Feature)"
            echo "  --filter FILTER   Run specific test by name pattern"
            echo "  --coverage        Generate code coverage report"
            echo "  --help            Show this help message"
            echo ""
            echo "Examples:"
            echo "  ./run-tests.sh                          # Run all tests"
            echo "  ./run-tests.sh --suite Unit             # Run only unit tests"
            echo "  ./run-tests.sh --suite Feature          # Run only feature tests"
            echo "  ./run-tests.sh --filter EncryptionHelper # Run tests matching name"
            echo "  ./run-tests.sh --coverage               # Run with coverage"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Build the test command
TEST_CMD="php artisan test"

if [ -n "$TEST_SUITE" ]; then
    TEST_CMD="$TEST_CMD --testsuite=$TEST_SUITE"
    echo -e "${BLUE}Running $TEST_SUITE tests...${NC}"
elif [ -n "$TEST_FILTER" ]; then
    TEST_CMD="$TEST_CMD --filter=$TEST_FILTER"
    echo -e "${BLUE}Running tests matching: $TEST_FILTER${NC}"
else
    echo -e "${BLUE}Running all tests...${NC}"
fi

if [ "$COVERAGE" = true ]; then
    TEST_CMD="$TEST_CMD --coverage"
    echo -e "${YELLOW}Generating coverage report...${NC}"
fi

echo ""

# Run the tests
eval $TEST_CMD

# Check exit code
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}==================================${NC}"
    echo -e "${GREEN}  All tests passed! ✓${NC}"
    echo -e "${GREEN}==================================${NC}"
else
    echo ""
    echo -e "${RED}==================================${NC}"
    echo -e "${RED}  Some tests failed! ✗${NC}"
    echo -e "${RED}==================================${NC}"
    exit 1
fi
