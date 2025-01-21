<?php
require_once 'TributeProcessor.php';

class TributeProcessorTest {
    public static function testCorrections() {
        // Mock database connection
        $conn = null;
        $processor = new TributeProcessor($conn);
        
        // Test cases - real-world examples of typical tributes
        $testCases = [
            "my dear friend john, i will miss u so much.you were always there for me!!!!!",
            "To aunty mary who taught me everything i know about life... she was the Best",
            "mr. Smith was my teacher at st. james school.he taught me alot.",
            "i want 2 say thankyou 4 everything u did 4 our church community",
            "Pastor James Williams led our congregation with wisdom&grace. we miss him dearly"
        ];
        
        echo "Running Test Cases:\n\n";
        
        foreach ($testCases as $index => $text) {
            echo "Test Case #" . ($index + 1) . ":\n";
            echo "Original:  " . $text . "\n";
            
            // Use reflection to access private method
            $reflection = new ReflectionClass('TributeProcessor');
            $method = $reflection->getMethod('correctText');
            $method->setAccessible(true);
            
            $corrected = $method->invoke($processor, $text);
            echo "Corrected: " . $corrected . "\n";
            
            // Calculate and show difference percentage
            similar_text($text, $corrected, $percent);
            echo "Difference: " . round(100 - $percent, 2) . "%\n\n";
        }
    }
}

// Run the tests
TributeProcessorTest::testCorrections();
?>