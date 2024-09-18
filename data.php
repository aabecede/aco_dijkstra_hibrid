<?php

$graph = [
    'A' => ['B' => 2, 'C' => 2, 'D' => 3],
    'B' => ['E' => 4],
    'C' => ['B' => 3, 'F' => 2, 'D' => 4],
    'D' => ['H' => 7],
    'E' => ['F' => 2, 'G' => 3],
    'F' => [],
    'G' => ['H' => 1],
    'H' => []
];
$pheromones = initializePheromones($graph);

function initializePheromones($graph) {
    $pheromones = [];
    foreach ($graph as $node => $neighbors) {
        foreach ($neighbors as $neighbor => $cost) {
            $edge = "{$node}-{$neighbor}";
            $pheromones[$edge] = 1.0;
        }
    }
    return $pheromones;
}

function runACO($graph, &$pheromones, $startNode, $endNode, $numAnts, $numIterations, $alpha = 1.0, $beta = 2.0, $evaporationRate = 0.5) {
    $bestPath = null;
    $bestPathLength = PHP_INT_MAX;
    $allAntPaths = [];

    for ($iter = 0; $iter < $numIterations; $iter++) {
        $iterationPaths = [];

        for ($ant = 0; $ant < $numAnts; $ant++) {
            $path = [$startNode];
            $visited = [$startNode => true];
            $currentNode = $startNode;

            while ($currentNode !== $endNode) {
                $nextNode = chooseNextNode($currentNode, $graph, $pheromones, $visited, $alpha, $beta);
                if ($nextNode === null) break;  // Jika jalan buntu

                $path[] = $nextNode;
                $visited[$nextNode] = true;
                $currentNode = $nextNode;
            }

            $pathLength = calculatePathLength($path, $graph);
            if ($pathLength < $bestPathLength && end($path) === $endNode) {
                $bestPath = $path;
                $bestPathLength = $pathLength;
            }

            $iterationPaths[] = $path;
        }

        $allAntPaths[] = $iterationPaths;
        updatePheromones($iterationPaths, $pheromones, $graph, $bestPathLength, $evaporationRate);
    }

    return ['bestPath' => $bestPath, 'bestPathLength' => $bestPathLength, 'antPaths' => $allAntPaths];
}

function chooseNextNode($currentNode, $graph, $pheromones, $visited, $alpha, $beta) {
    $neighbors = $graph[$currentNode];
    $total = 0;
    $probabilities = [];

    foreach ($neighbors as $neighbor => $cost) {
        if (!isset($visited[$neighbor])) {
            $edge = "{$currentNode}-{$neighbor}";
            $tau = pow($pheromones[$edge], $alpha);
            $eta = pow(1 / $cost, $beta);
            $probabilities[$neighbor] = $tau * $eta;
            $total += $probabilities[$neighbor];
        }
    }

    if ($total == 0) return null;

    $rand = mt_rand() / mt_getrandmax() * $total;
    foreach ($probabilities as $neighbor => $probability) {
        $rand -= $probability;
        if ($rand <= 0) return $neighbor;
    }

    return null;
}

function calculatePathLength($path, $graph) {
    $length = 0;
    for ($i = 0; $i < count($path) - 1; $i++) {
        $length += $graph[$path[$i]][$path[$i + 1]];
    }
    return $length;
}

function updatePheromones($allPaths, &$pheromones, $graph, $bestPathLength, $evaporationRate) {
    // Menguapkan pheromone
    foreach ($pheromones as $edge => $value) {
        $pheromones[$edge] *= (1 - $evaporationRate);
    }

    // Menambahkan pheromone baru
    foreach ($allPaths as $path) {
        $pathLength = calculatePathLength($path, $graph);
        $depositAmount = 1 / $pathLength;
        for ($i = 0; $i < count($path) - 1; $i++) {
            $edge = "{$path[$i]}-{$path[$i + 1]}";
            $pheromones[$edge] += $depositAmount;
        }
    }
}

// Mendapatkan parameter dari URL
$numAnts = isset($_GET['numAnts']) ? intval($_GET['numAnts']) : 2;
$numIterations = isset($_GET['numIterations']) ? intval($_GET['numIterations']) : 2;
$startNode = isset($_GET['startNode']) ? $_GET['startNode'] : 'A';
$endNode = isset($_GET['endNode']) ? $_GET['endNode'] : 'H';

$result = runACO($graph, $pheromones, $startNode, $endNode, $numAnts, $numIterations);

header('Content-Type: application/json');
echo json_encode([
    'bestPath' => $result['bestPath'],
    'bestPathLength' => $result['bestPathLength'],
    'antPaths' => $result['antPaths'],
    'graph' => $graph,
    'pheromones' => $pheromones
]);
