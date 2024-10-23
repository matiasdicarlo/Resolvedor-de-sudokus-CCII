<?php
// Función para verificar si un número es válido en una posición dada
function es_valido($tablero, $fila, $columna, $numero) {
    /* Verificar fila
    Se recorre la fila para comprobar que el número no está presente*/
    for ($i = 0; $i < count($tablero); $i++) {
        if ($tablero[$fila][$i] == $numero) {
            return false;
        }
    }

    /* Verificar columna
    Igual que para las columnas se recorre la columna para comprobar que el numero no está presente*/
    for ($i = 0; $i < count($tablero); $i++) {
        if ($tablero[$i][$columna] == $numero) {
            return false;
        }
    }

    /* Verificar subcuadro 3x3
    Se calcula el subcuadro 3x3 correspondiente a la celda para comprobar que el numero no esta presente, luego 
    se verifica el subcuadro 3x3 para asegurarse de que el número no aparece en el mismo */
    $subcuadro_fila = floor($fila / 3) * 3;
    $subcuadro_columna = floor($columna / 3) * 3;
    for ($i = $subcuadro_fila; $i < $subcuadro_fila + 3; $i++) {
        for ($j = $subcuadro_columna; $j < $subcuadro_columna + 3; $j++) {
            if ($tablero[$i][$j] == $numero) {
                return false;
            }
        }
    }

    return true;
}

// Funcion para contar los valores posibles en una celda vacía
function contar_valores_posibles($tablero, $fila, $columna) {
    $posibles = range(1, 9); // valores posibles en sudoku 
    // Primero eliminar valores ya presentes en la fila
    for ($i = 0; $i < count($tablero); $i++) {
        if ($tablero[$fila][$i] != 0) {
            $indice = array_search($tablero[$fila][$i], $posibles);
            if ($indice !== false) {
                unset($posibles[$indice]);
            }
        }
    }
    // segundo eliminar valores ya presentes en la columna
    for ($i = 0; $i < count($tablero); $i++) {
        if ($tablero[$i][$columna] != 0) {
            $indice = array_search($tablero[$i][$columna], $posibles);
            if ($indice !== false) {
                unset($posibles[$indice]);
            }
        }
    }
    // por ultimo eliminar valores ya presentes en el subcuadro 3x3
    $subcuadro_fila = floor($fila / 3) * 3;
    $subcuadro_columna = floor($columna / 3) * 3;
    for ($i = $subcuadro_fila; $i < $subcuadro_fila + 3; $i++) {
        for ($j = $subcuadro_columna; $j < $subcuadro_columna + 3; $j++) {
            if ($tablero[$i][$j] != 0) {
                $indice = array_search($tablero[$i][$j], $posibles);
                if ($indice !== false) {
                    unset($posibles[$indice]);
                }
            }
        }
    }
    return $posibles;
}

// Función para encontrar la celda vacía con menos valores posibles (MRV)
function encontrar_celda_MRV($tablero, $dominios) {
    $min_valores = 10; // Inicialmente más grande que cualquier número de posibles valores (Sudoku de 9x9)
    $mejor_celda = null;

    for ($fila = 0; $fila < count($tablero); $fila++) {
        for ($columna = 0; $columna < count($tablero[0]); $columna++) {
            if ($tablero[$fila][$columna] == 0) {
                $num_posibles = count($dominios[$fila][$columna]);  //Se recorre el tablero buscando las celdas vacías y se cuenta cuántos valores posibles tiene cada celda.
                if ($num_posibles < $min_valores) {
                    $min_valores = $num_posibles;
                    $mejor_celda = array($fila, $columna);
                }
            }
        }
    }

    return $mejor_celda;
}

// Función para aplicar AC-3 y verificar consistencia de los dominios
function AC3($tablero, &$dominios) {
    $cola_arcos = array();

    // Inicializar la cola con todos los arcos (Vecinos /Fila/Columna/Subcuadrado)
    for ($fila = 0; $fila < count($tablero); $fila++) {
        for ($columna = 0; $columna < count($tablero); $columna++) {
            if ($tablero[$fila][$columna] == 0) {
                foreach (obtener_vecinos($fila, $columna) as $vecino) {  //Se inicializa un array, que contiene pares de celdas relacionadas (vecinas) en el tablero.
                    array_push($cola_arcos, array(array($fila, $columna), $vecino));
                }
            }
        }
    }

    // Procesar la cola de arcos y revisa cada arco, si un arco cambia, se vuelven a revisar sus vecinos.
    while (!empty($cola_arcos)) {
        list($celda1, $celda2) = array_shift($cola_arcos);
        if (revisar_arco($tablero, $dominios, $celda1, $celda2)) {
            foreach (obtener_vecinos($celda1[0], $celda1[1]) as $vecino) {
                array_push($cola_arcos, array($vecino, $celda1));
            }
        }
    }
}

// Verificar si hay inconsistencias en el dominio
function revisar_arco($tablero, &$dominios, $celda1, $celda2) {  //Celda1 y celda2 son arrays que representan la posición de dos celdas en el tablero, cada una con dos valores: fila y columna.
    $modificado = false; //se usa para rastrear si algún dominio ha sido modificado.
    $fila1 = $celda1[0]; // se extrae la fila y la columna de la primera celda ($celda1)
    $columna1 = $celda1[1]; 
    $fila2 = $celda2[0]; //se extrae la fila y la columna de la segunda celda ($celda2)
    $columna2 = $celda2[1];

    if (count($dominios[$fila2][$columna2]) == 1) { /*Se verifica si la celda 2 tiene solo un valor posible en su dominio (es decir, si su conjunto de valores posibles tiene solo un elemento)
        Si hay solo un valor en la celda 2, podemos intentar eliminar ese valor del dominio de la celda 1, ya que de lo contrario ambos tendrían el mismo valor, lo cual es inconsistente*/ 
        $valor_vecino = reset($dominios[$fila2][$columna2]); //Se obtiene el único valor en el dominio de la celda 2. 
        if (in_array($valor_vecino, $dominios[$fila1][$columna1])) {  //se verifica si ese valor ($valor_vecino) esta presente en el dominio de la celda 1. Si está presente, significa que hay una inconsistencia, ya que no se puede asignar el mismo valor a ambas celdas (según las reglas del Sudoku).
            $dominios[$fila1][$columna1] = array_diff($dominios[$fila1][$columna1], array($valor_vecino));
            $modificado = true;
        }
    } //En este punto se elimino $valor_vecino del dominio de la celda 1 usando array_diff(). Esta función devuelve un nuevo array que contiene los valores de $dominios[$fila1][$columna1] excluyendo $valor_vecino.
    // se busca eliminar el valor del dominio de la celda 1, ya que ese valor ya está asignado a la celda 2.

    return $modificado; //La funcion devuelve true si se modificó el dominio de la celda 1 (lo cual significa que se ha encontrado y corregido una inconsistencia), o false si no se ha realizado ningún cambio.
}

// Obtener celdas vecinas de una celda (misma fila, columna o subcuadro)
function obtener_vecinos($fila, $columna) {
    $vecinos = array();

    // Vecinos en la misma fila y columna
    for ($i = 0; $i < 9; $i++) {
        $vecinos[] = array($fila, $i); // Misma fila
        $vecinos[] = array($i, $columna); // Misma columna
    }

    // Vecinos en el mismo subcuadro
    $subcuadro_fila = floor($fila / 3) * 3;
    $subcuadro_columna = floor($columna / 3) * 3;
    for ($i = $subcuadro_fila; $i < $subcuadro_fila + 3; $i++) {
        for ($j = $subcuadro_columna; $j < $subcuadro_columna + 3; $j++) {
            $vecinos[] = array($i, $j);
        }
    }

    // Eliminar la celda misma
    $vecinos = array_filter($vecinos, function($vecino) use ($fila, $columna) {
        return $vecino != array($fila, $columna);
    });

    return $vecinos;
}

// Forward checking (verificación hacia adelante), eliminar posibles valores en los vecinos
function forward_checking($tablero, &$dominios, $fila, $columna, $valor) {
    foreach (obtener_vecinos($fila, $columna) as $vecino) {   //Se recorre todos los vecinos de la celda ubicada en la fila $fila y columna $columna. La función obtener_vecinos devuelve un conjunto de coordenadas (fila, columna) de todas las celdas que comparten la misma fila, columna o subcuadro 3x3 con la celda dada.
        list($fila_vecino, $columna_vecino) = $vecino; //se descompone el array $vecino en dos variables: $fila_vecino y $columna_vecino, que representan las coordenadas de una celda vecina.
        if (in_array($valor, $dominios[$fila_vecino][$columna_vecino])) {  //se verifica si el valor asignado a la celda actual está presente en el dominio de la celda vecina. Si está presente, significa que este valor es una posibilidad para la celda vecina, pero ahora debe eliminarse porque ya está asignado a la celda actual.
            $dominios[$fila_vecino][$columna_vecino] = array_diff($dominios[$fila_vecino][$columna_vecino], array($valor)); //se elimina el valor asignado de los posibles valores (dominio) de la celda vecina.
            if (count($dominios[$fila_vecino][$columna_vecino]) == 0) {   //después de eliminar el valor, se verifica si el dominio de la celda vecina ha quedado vacío, si el dominio está vacío, significa que no quedan valores válidos para la celda vecina, lo que causa una falla en la búsqueda.
                return false; // Si algún vecino no tiene más valores posibles, falla
            }
        }
    }
    return true;
}
// Función para contar las restricciones generadas por un valor en los vecinos, esta función se utiliza para implementar el heurístico LCV (Least Constraining Value)
function contar_restricciones($tablero, $fila, $columna, $valor, $dominios) {
    $restricciones = 0;  //se inicializa el contador de restricciones a cero, este contador se incrementará cada vez que el valor dado esté presente en el dominio de un vecino
    $vecinos = obtener_vecinos($fila, $columna); //Se llama a la función obtener_vecinos para obtener todas las celdas que comparten la misma fila, columna o subcuadro con la celda ubicada en la fila $fila y la columna $columna
    foreach ($vecinos as $vecino) { //Se itera sobre cada vecino de la celda actual
        list($fila_vecino, $columna_vecino) = $vecino; //Se desarma el array $vecino en dos variables: $fila_vecino y $columna_vecino, que representan las coordenadas de una celda vecina.
        if (in_array($valor, $dominios[$fila_vecino][$columna_vecino])) { //Se verifica si el valor que se está probando en la celda actual está presente en el dominio de la celda vecina, si está presente, significa que este valor es una opción para la celda vecina, y, por lo tanto, genera una restricción.
            $restricciones++;
        }
    }
    return $restricciones; //la función retorna el número de restricciones que genera el valor dado en los vecinos de la celda actual
}





// Función para resolver el Sudoku con MRV, LCV, Forward Checking y AC-3 UNA DE LAS FUNCIONES PRINCIPALES o PUNTO DE PARTIDA
function resolver_sudoku(&$tablero, &$dominios) {
    $celda = encontrar_celda_MRV($tablero, $dominios);  //Se busca la celda más restringida MRV
    
    if ($celda === null) {  //Si no se encuentra ninguna celda con valores posibles, el Sudoku está completamente resuelto, por lo que la función retorna true
        return true;
    }

    list($fila, $columna) = $celda; //Desestructura la posición de la celda encontrada (fila y columna)

    $posibles_valores = $dominios[$fila][$columna]; //se obtiene el dominio de la celda seleccionada
    usort($posibles_valores, function($v1, $v2) use ($tablero, $fila, $columna, $dominios) {
        return contar_restricciones($tablero, $fila, $columna, $v1, $dominios) - contar_restricciones($tablero, $fila, $columna, $v2, $dominios);
    }); //ordena los posibles valores usando LCV, se evalúa cada valor según cuántas restricciones generaría en los vecinos, ordenando de menor a mayor restricciones
    
    foreach ($posibles_valores as $valor) { //Se intenta cada valor del dominio de la celda, comenzando por el valor que menos restricciones genera (Tener en cuenta se ordenaron segun LCV en la linea anterior)
        if (es_valido($tablero, $fila, $columna, $valor)) { //se comprueba si el valor es válido en la posición actual (es decir, si no viola ninguna restricción de Sudoku en la fila, columna o subcuadro)
            $tablero[$fila][$columna] = $valor;
            $dominio_copia = $dominios; //se guarda una copia del dominio actual para poder restaurarlo en caso de que el intento actual falle
            
            if (forward_checking($tablero, $dominios, $fila, $columna, $valor)) { //Si el valor es válido, se realiza forward checking para actualizar los dominios de los vecinos y eliminar el valor asignado de sus posibles dominios, si forward checking falla (es decir, si algún vecino se queda sin valores), se retrocede (BACKTRAKING)
                AC3($tablero, $dominios); //Tras el forward checking, se asegura la consistencia de los arcos usando AC-3, que elimina valores inconsistentes de los dominios de los vecinos
                if (resolver_sudoku($tablero, $dominios)) {
                    return true;  //Llamada recursiva a la misma función para intentar resolver el resto del tablero con la asignación actual, si se resuelve, la función retorna true
                }
            }
            
            $tablero[$fila][$columna] = 0; //Si la solución actual no es válida, se deshace la asignación y se restaura el tablero
            $dominios = $dominio_copia;
        }
    }

    return false; //Si no se encuentra una solución con los valores probados, se retrocede, devolviendo false para intentar nuevas asignaciones
}

// Función para inicializar el tablero y los dominios
function inicializar_sudoku($tablero) {
    $dominios = [];
    for ($i = 0; $i < 9; $i++) {
        for ($j = 0; $j < 9; $j++) {
            if ($tablero[$i][$j] == 0) {
                $dominios[$i][$j] = contar_valores_posibles($tablero, $i, $j);
            } else {
                $dominios[$i][$j] = [];
            }
        }
    }
    return $dominios;
}

function arrancar_algoritmo($tablero){
    //Se inicializan los dominios 
    $dominios = inicializar_sudoku($tablero);

    if (resolver_sudoku($tablero, $dominios)) {
        echo "Sudoku resuelto\n";
        foreach ($tablero as $fila) {
            echo implode(" ", $fila) . "\n";
        }
    } else {
        echo "No tiene solución.\n";
    }
    

}

/*

De aqui en adelante no hace al funcionamiento del algoritmo de resolucion del tablero de sudoku, 
tiene como funcion simplemente permitir cargar el tablero, y luego dar inicio al algoritmo de resolución

*/

// Función para cargar el tablero desde una entrada en formato matriz
function cargar_tablero() {
    echo "Ingrese el tablero en formato matriz (ejemplo: [[5,3,0,...],[...],...]):\n";
    $input = trim(fgets(STDIN));  // Leer la entrada del usuario
    // Decodificar la entrada ingresado a un array PHP
    $tablero = json_decode($input, true);
    // Verificar si la decodificación fue exitosa y si el tablero tiene el tamaño correcto
    if (!is_array($tablero) || count($tablero) != 9) {
        echo "Error: El tablero debe tener exactamente 9 filas.\n";
        return null;
    }
    foreach ($tablero as $fila) {
        if (!is_array($fila) || count($fila) != 9) {
            echo "Error: Cada fila debe tener exactamente 9 valores.\n";
            return null;
        }
    }
    return $tablero;
}

// Función para imprimir el tablero
function imprimir_tablero($tablero) {
    foreach ($tablero as $fila) {
        echo implode(' ', $fila) . "\n";
    }
}

// Cargar el tablero desde la entrada
$tablero = cargar_tablero();

if ($tablero) {
    echo "\nTablero ingresado:\n";
    imprimir_tablero($tablero);
    echo "\n";
    arrancar_algoritmo($tablero); //Se inicia el proceso de resolución
}


