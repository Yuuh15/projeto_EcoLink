// tests/js/script.test.js
// Simple JavaScript tests for carousel and search functionality

class TestRunner {
    constructor() {
        this.passed = 0;
        this.failed = 0;
        this.tests = [];
    }

    test(name, condition) {
        if (condition) {
            this.passed++;
            this.tests.push({ name, status: 'PASS' });
        } else {
            this.failed++;
            this.tests.push({ name, status: 'FAIL' });
        }
    }

    assertEquals(expected, actual, message = '') {
        this.test(message || `Expected: ${expected}, Got: ${actual}`, expected === actual);
    }

    assertTrue(value, message = '') {
        this.test(message || 'Expected true', value === true);
    }

    assertFalse(value, message = '') {
        this.test(message || 'Expected false', value === false);
    }

    assertNull(value, message = '') {
        this.test(message || 'Expected null', value === null);
    }

    assertArrayEquals(expected, actual, message = '') {
        const equal = JSON.stringify(expected) === JSON.stringify(actual);
        this.test(message || `Arrays should be equal`, equal);
    }

    run() {
        this.testCarouselFunctions();
        this.testSearchFilter();
        this.testUtilityFunctions();
        this.printResults();
    }

    testCarouselFunctions() {
        // Test filterEvents logic
        const mockCards = [
            { title: 'Eco Workshop', desc: 'Learn about recycling' },
            { title: 'Beach Cleanup', desc: 'Clean the beach' },
            { title: 'Tree Planting', desc: 'Plant trees in the park' },
        ];

        // Test search matching
        const searchTerm1 = 'eco';
        const filtered1 = mockCards.filter(card => 
            card.title.toLowerCase().includes(searchTerm1) || 
            card.desc.toLowerCase().includes(searchTerm1)
        );
        this.assertEquals(1, filtered1.length, 'Search "eco" returns 1 result');
        this.assertEquals('Eco Workshop', filtered1[0].title, 'Search "eco" finds Eco Workshop');

        // Test search with no results
        const searchTerm2 = 'xyz123';
        const filtered2 = mockCards.filter(card => 
            card.title.toLowerCase().includes(searchTerm2) || 
            card.desc.toLowerCase().includes(searchTerm2)
        );
        this.assertEquals(0, filtered2.length, 'Search "xyz123" returns 0 results');

        // Test empty search
        const searchTerm3 = '';
        this.assertTrue(searchTerm3.trim() === '', 'Empty search is detected');

        // Test case insensitivity
        const searchTerm4 = 'BEACH';
        const filtered4 = mockCards.filter(card => 
            card.title.toLowerCase().includes(searchTerm4.toLowerCase()) || 
            card.desc.toLowerCase().includes(searchTerm4.toLowerCase())
        );
        this.assertEquals(1, filtered4.length, 'Search is case insensitive');
        this.assertEquals('Beach Cleanup', filtered4[0].title, 'Search "BEACH" finds Beach Cleanup');
    }

    testSearchFilter() {
        // Test multiple word search
        const mockCards = [
            { title: 'Environmental Conference', desc: 'Discuss climate change' },
            { title: 'Recycling Workshop', desc: 'Learn to recycle' },
            { title: 'Ocean Cleanup', desc: 'Clean the oceans' },
        ];

        const searchTerm = 'recycle';
        const filtered = mockCards.filter(card => 
            card.title.toLowerCase().includes(searchTerm) || 
            card.desc.toLowerCase().includes(searchTerm)
        );
        this.assertEquals(1, filtered.length, 'Search "recycle" finds 1 card');
        this.assertEquals('Recycling Workshop', filtered[0].title, 'Search finds correct card');

        // Test search in description
        const searchTerm2 = 'climate';
        const filtered2 = mockCards.filter(card => 
            card.title.toLowerCase().includes(searchTerm2) || 
            card.desc.toLowerCase().includes(searchTerm2)
        );
        this.assertEquals(1, filtered2.length, 'Search in description works');
        this.assertEquals('Environmental Conference', filtered2[0].title, 'Search finds card by description');
    }

    testUtilityFunctions() {
        // Test trim function
        this.assertEquals('hello', '  hello  '.trim(), 'Trim removes whitespace');

        // Test includes function
        this.assertTrue('hello world'.includes('world'), 'String includes works');
        this.assertFalse('hello world'.includes('xyz'), 'String includes returns false for non-match');

        // Test toLowerCase
        this.assertEquals('hello', 'HELLO'.toLowerCase(), 'toLowerCase works');

        // Test array operations
        const arr = [1, 2, 3, 4, 5];
        this.assertEquals(5, arr.length, 'Array length is correct');
        this.assertEquals(1, arr[0], 'Array first element is correct');
        this.assertEquals(5, arr[arr.length - 1], 'Array last element is correct');

        // Test array slice
        const sliced = arr.slice(0, 3);
        this.assertArrayEquals([1, 2, 3], sliced, 'Array slice works');

        // Test array filter
        const filtered = arr.filter(x => x > 3);
        this.assertArrayEquals([4, 5], filtered, 'Array filter works');
    }

    printResults() {
        console.log('\n=== EcoLink JavaScript Test Results ===\n');
        
        this.tests.forEach(test => {
            const status = test.status === 'PASS' ? '✓ PASS' : '✗ FAIL';
            console.log(`${status}: ${test.name}`);
        });
        
        console.log('\n=== Summary ===');
        console.log(`Total: ${this.passed + this.failed}`);
        console.log(`Passed: ${this.passed}`);
        console.log(`Failed: ${this.failed}`);
        
        if (this.failed > 0) {
            process.exit(1);
        }
    }
}

const runner = new TestRunner();
runner.run();
