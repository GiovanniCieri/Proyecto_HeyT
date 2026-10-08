——— EL EJERCICIO ———


Adjunto un zip con dos cosas: una API de un POS ficticio corriendo en tu máquina, y la documentación oficial de esa API.
Para levantarla:
    python3 mock_server.py
Solo necesita Python 3.9 o superior. No instala nada, no necesita internet, no necesita ninguna cuenta.
Escribí una integración chica, en el lenguaje que quieras, que:
1. Se autentique.
2. Traiga todas las locations.
3. Traiga el menú de cada location.
4. Cree una orden en la location que se le pase por parámetro, con el item que se le pase por nombre (por ejemplo "Buffalo Wings (12)"), cantidad 2.
5. Imprima un resumen: si la orden se creó, el id y el total.
Tiene que poder correr dos veces seguidas sin duplicar órdenes.
Entregás tres cosas:
1. El código corriendo. Decime el comando exacto.
2. Un README de media página con las decisiones que tomaste y por qué.
3. Una lista de lo que dejaste afuera a propósito.
Dos avisos. Primero: la documentación tiene errores, huecos y cosas desactualizadas. El comportamiento real de la API es la fuente de verdad, y parte del ejercicio es que encuentres las diferencias y decidas qué hacer con cada una. Segundo: podés usar IA, solo pedimos que lo declares. En la etapa 2 vamos a leer el código juntos.


——— FIN DEL EJERCICIO ———
